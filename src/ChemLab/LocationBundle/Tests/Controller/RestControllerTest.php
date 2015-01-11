<?php
namespace ChemLab\LocationBundle\Tests\Controller;

use ChemLab\LocationBundle\Entity\Location;
use ChemLab\Utilities\RESTWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestControllerTest extends RESTWebTestCase {

	public function setUp() {
		parent::setUp();
		$this->setEntityClass('\ChemLab\LocationBundle\Entity\Location');
	}

	public function assertPreConditions() {

		$this->assertEquals(2, $this->getEntityCount());

		$this->entities = $this->repository->findAll();

	}

	public function testGetEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$client->request(Request::METHOD_GET, "/locations/locs/$ids[0]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, "/locations/locs/$ids[0]");
		$this->assertTrue($client->getResponse()->isForbidden());

		// Richiesta non valida
		$this->fakeLogin('admin');
		$client->request(Request::METHOD_GET, '/locations/locs/Armadio A');
		$this->assertTrue($client->getResponse()->isNotFound());

		$client->request(Request::METHOD_GET, "/locations/locs/$ids[0]");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals([ 'id', 'name', 'position', 'capacity', 'notes' ], array_keys($json));

		$this->assertTrue(is_integer($json['capacity']));
		foreach ([ 'name', 'position', 'notes' ] as $prop)
			$this->assertTrue(is_string($json[$prop]));

		$nonexistent = call_user_func_array('max', $ids) + 1;
		$client->request(Request::METHOD_GET, "/locations/locs/$nonexistent");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('name', $json));
		$this->assertArrayHasKey('error', $json);
	}

	public function testPutPatchEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array( 'position' => $this->entities[0]->getPosition() ));
		$client->request(Request::METHOD_PATCH, "/locations/locs/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_PATCH, "/locations/locs/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');
		$client->request(Request::METHOD_PATCH, "/locations/locs/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

		$client->request(Request::METHOD_PUT, "/locations/locs/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
		$this->fakeLogout();
	}

	public function testPostDeleteEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array(
			'name' => 'Ripiano R',
			'position' => 'Sopra di te',
			'capacity' => 50,
			'notes' => ''
		));
		$invalid = json_encode(array(
			'name' => 'Ripiano R'
		));

		$client->request(Request::METHOD_POST, '/locations/locs/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$client->request(Request::METHOD_DELETE, "/locations/locs/$ids[1]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_POST, '/locations/locs/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$client->request(Request::METHOD_DELETE, "/locations/locs/$ids[1]");
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');

		// Inserimento dati non validi
		$client->request(Request::METHOD_POST, '/locations/locs/0', [], [], [], $invalid);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
		$this->assertArrayHasKey('fields', $json);
		$this->assertEquals([ 'position' ], array_keys($json['fields']));

		// Inserimento dati corretti
		$client->request(Request::METHOD_POST, '/locations/locs/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newentity = $this->repository->findOneByName('Ripiano R');
		$this->assertTrue($newentity !== null);
		$this->assertEquals('Sopra di te', $newentity->getPosition());

		$newid = $newentity->getId();
		$client->request(Request::METHOD_DELETE, "/locations/locs/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Cancellazione utente non esistente
		$client->request(Request::METHOD_DELETE, "/locations/locs/$newid");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
	}

	public function testList() {
		$client = $this->client;

		$client->request(Request::METHOD_GET, '/locations/locs/0-2');
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti comuni non possono accedere al servizio
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/locations/locs/0-2');
		$this->assertTrue($client->getResponse()->isForbidden());
		$this->fakeLogout();

		$this->fakeLogin('thequeen');
		$client->request(Request::METHOD_GET, '/locations/locs/0-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);
		$this->assertArrayHasKey('total', $json);

		// Richiesta non formalmente valida
		$client->request(Request::METHOD_GET, '/locations/locs/-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isNotFound());

		// Richiesta formalmente valida ma errata
		$client->request(Request::METHOD_GET, '/locations/locs/7-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('list', $json));
		$this->assertArrayHasKey('error', $json);

		// Richiesta filtrata
		$client->request(Request::METHOD_GET, '/locations/locs/0-9?name=Arm');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(1, $json['total']);

		// Richiesta con ordinamento
		$client->request(Request::METHOD_GET, '/locations/locs/0-9/-capacity');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);

		$values = array_map(function($entity) { return $entity['capacity']; }, $json['list']);
		$sorted = $values;
		sort($sorted);
		$sorted = array_reverse($sorted);
		$this->assertEquals($values, $sorted);
	}

}