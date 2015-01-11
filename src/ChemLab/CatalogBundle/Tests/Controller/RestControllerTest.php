<?php
namespace ChemLab\CatalogBundle\Tests\Controller;

use ChemLab\CatalogBundle\Entity\Item;
use ChemLab\Utilities\RESTWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestControllerTest extends RESTWebTestCase {

	public function setUp() {
		parent::setUp();
		$this->setEntityClass('\ChemLab\CatalogBundle\Entity\Item');
	}

	public function assertPreConditions() {

		$this->assertEquals(2, $this->getEntityCount());

		$this->entities = $this->repository->findAll();

	}

	public function testGetItems() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$client->request(Request::METHOD_GET, "/catalog/items/$ids[0]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, "/catalog/items/$ids[0]");
		$this->assertTrue($client->getResponse()->isForbidden());

		// Richiesta non valida
		$this->fakeLogin('admin');
		$client->request(Request::METHOD_GET, '/catalog/items/admin');
		$this->assertTrue($client->getResponse()->isNotFound());

		$client->request(Request::METHOD_GET, "/catalog/items/$ids[0]");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals([ 'id', 'name', 'description', 'code', 'type', 'price' ], array_keys($json));

		foreach ([ 'name', 'description', 'code', 'type' ] as $prop)
			$this->assertTrue(is_string($json[$prop]));
		foreach ([ 'id', 'price' ] as $prop)
			$this->assertTrue(is_numeric($json[$prop]));

		$nonexistent = call_user_func_array('max', $ids) + 1;
		$client->request(Request::METHOD_GET, "/catalog/items/$nonexistent");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('name', $json));
		$this->assertArrayHasKey('error', $json);
	}

	public function testPutPatchItems() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array( 'description' => $this->entities[0]->getDescription() ));
		$client->request(Request::METHOD_PATCH, "/catalog/items/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_PATCH, "/catalog/items/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');
		$client->request(Request::METHOD_PATCH, "/catalog/items/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

		$client->request(Request::METHOD_PUT, "/catalog/items/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
		$this->fakeLogout();
	}

	public function testPostDeleteItems() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array(
			'name' => 'Provetta 50ml',
			'description' => 'In vetro',
			'code' => 'PRO50',
			'type' => 'glassware',
			'price' => 1.2
		));
		$invalid = json_encode(array(
			'name' => 'Provetta 50ml',
			'type' => 'glassware'
		));

		$client->request(Request::METHOD_POST, '/catalog/items/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$client->request(Request::METHOD_DELETE, "/catalog/items/$ids[1]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_POST, '/catalog/items/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$client->request(Request::METHOD_DELETE, "/catalog/items/$ids[1]");
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');

		// Inserimento dati non validi
		$client->request(Request::METHOD_POST, '/catalog/items/0', [], [], [], $invalid);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
		$this->assertArrayHasKey('fields', $json);
		$this->assertEquals([ 'code' ], array_keys($json['fields']));

		// Inserimento dati corretti
		$client->request(Request::METHOD_POST, '/catalog/items/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newitem = $this->repository->findOneByCode('PRO50');
		$this->assertTrue($newitem !== null);
		$this->assertEquals('Provetta 50ml', $newitem->getName());

		$newid = $newitem->getId();
		$client->request(Request::METHOD_DELETE, "/catalog/items/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Cancellazione utente non esistente
		$client->request(Request::METHOD_DELETE, "/catalog/items/$newid");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
	}

	public function testList() {
		$client = $this->client;

		$client->request(Request::METHOD_GET, '/catalog/items/0-2');
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti comuni non possono accedere al servizio
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/catalog/items/0-2');
		$this->assertTrue($client->getResponse()->isForbidden());
		$this->fakeLogout();

		$this->fakeLogin('thequeen');
		$client->request(Request::METHOD_GET, '/catalog/items/0-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);
		$this->assertArrayHasKey('total', $json);

		// Richiesta non formalmente valida
		$client->request(Request::METHOD_GET, '/catalog/items/-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isNotFound());

		// Richiesta formalmente valida ma errata
		$client->request(Request::METHOD_GET, '/catalog/items/7-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('list', $json));
		$this->assertArrayHasKey('error', $json);

		// Richiesta filtrata
		$client->request(Request::METHOD_GET, '/catalog/items/0-9?type=solvent');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(1, $json['total']);

		// Richiesta con ordinamento
		$client->request(Request::METHOD_GET, '/catalog/items/0-9/-code');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);

		$codes = array_map(function($entity) { return $entity['code']; }, $json['list']);
		$sorted = $codes;
		sort($sorted);
		$sorted = array_reverse($sorted);
		$this->assertEquals($codes, $sorted);
	}

}