<?php
namespace ChemLab\InventoryBundle\Tests\Controller;

use ChemLab\InventoryBundle\Entity\Entry;
use ChemLab\Utilities\RESTWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestControllerTest extends RESTWebTestCase {
	const LOC_CLASS = '\ChemLab\LocationBundle\Entity\Location';
	const ITEM_CLASS = '\ChemLab\CatalogBundle\Entity\Item';

	/**
	 * @var \ChemLab\CatalogBundle\Entity\Item[]
	 */
	protected $items;

	/**
	 * @var \ChemLab\LocationBundle\Entity\Location[]
	 */
	protected $locs;

	public function setUp() {
		parent::setUp();
		$this->setEntityClass('\ChemLab\InventoryBundle\Entity\Entry');

		$this->items = $this->manager->getRepository(static::ITEM_CLASS)->findAll();
		$this->locs = $this->manager->getRepository(static::LOC_CLASS)->findAll();
	}

	public function assertPreConditions() {

		$this->assertEquals(2, $this->getEntityCount());
		$this->assertEquals(2, $this->getEntityCount(null, static::ITEM_CLASS));
		$this->assertEquals(2, $this->getEntityCount(null, static::LOC_CLASS));

		$this->entities = $this->repository->findAll();

	}

	public function testGetEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$client->request(Request::METHOD_GET, "/inventory/entries/$ids[0]");
		$this->assertTrue($client->getResponse()->isRedirect());

		// Richiesta non valida
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/inventory/entries/pippo');
		$this->assertTrue($client->getResponse()->isNotFound());

		$client->request(Request::METHOD_GET, "/inventory/entries/$ids[0]");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals([ 'id', 'item', 'location', 'quantity', 'notes' ], array_keys($json));

		foreach ([ 'id', 'quantity' ] as $prop)
			$this->assertTrue(is_numeric($json[$prop]));
		foreach ([ 'notes' ] as $prop)
			$this->assertTrue(is_string($json[$prop]));
		foreach ([ 'item', 'location' ] as $prop)
			$this->assertTrue(is_array($json[$prop]));

		$nonexistent = call_user_func_array('max', $ids) + 1;
		$client->request(Request::METHOD_GET, "/inventory/entries/$nonexistent");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('item', $json));
		$this->assertArrayHasKey('error', $json);
	}

	public function testPutPatchEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array( 'quantity' => $this->entities[0]->getQuantity() + 1 ));

		$client->request(Request::METHOD_PATCH, "/inventory/entries/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti normali possono fare PUT/PATCH
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_PATCH, "/inventory/entries/$ids[0]", [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

		$client->request(Request::METHOD_PUT, "/inventory/entries/$ids[0]", [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
		$this->fakeLogout();
	}

	public function testPostDeleteEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array(
			'item' => $this->items[0]->getId(),
			'location' => $this->locs[1]->getId(),
			'quantity' => 16
		));
		$invalid = json_encode(array(
			'item' => $this->items[0]->getId()
		));

		$client->request(Request::METHOD_POST, '/inventory/entries/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$client->request(Request::METHOD_DELETE, "/inventory/entries/$ids[1]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		// Gli utenti comuni possono fare POST
		$client->request(Request::METHOD_POST, '/inventory/entries/0', [], [], [], $data);
		echo $client->getResponse()->getContent();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// ... ma non DELETE
		$client->request(Request::METHOD_DELETE, "/inventory/entries/$ids[1]");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);

		$this->fakeLogin('admin');

		// Inserimento dati non validi
		$client->request(Request::METHOD_POST, '/inventory/entries/0', [], [], [], $invalid);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
		$this->assertArrayHasKey('fields', $json);
		$this->assertEquals([ 'location' ], array_keys($json['fields']));

		// Cancellazione entità precedente
		$newentity = $this->repository->findOneByQuantity(16);
		$this->assertTrue($newentity !== null);
		$this->assertEquals(16, $newentity->getQuantity());

		$newid = $newentity->getId();
		$client->request(Request::METHOD_DELETE, "/inventory/entries/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Reinserimento dati corretti
		$client->request(Request::METHOD_POST, '/inventory/entries/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newentity = $this->repository->findOneByQuantity(16);
		$this->assertTrue($newentity !== null);
		$this->assertEquals(16, $newentity->getQuantity());

		$newid = $newentity->getId();
		$client->request(Request::METHOD_DELETE, "/inventory/entries/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Cancellazione utente non esistente
		$client->request(Request::METHOD_DELETE, "/inventory/entries/$newid");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
	}

	public function testList() {
		$client = $this->client;

		$client->request(Request::METHOD_GET, '/inventory/entries/0-2');
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti comuni possono accedere al servizio
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/inventory/entries/0-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);
		$this->assertArrayHasKey('total', $json);

		// Richiesta non formalmente valida
		$client->request(Request::METHOD_GET, '/inventory/entries/-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isNotFound());

		// Richiesta formalmente valida ma errata
		$client->request(Request::METHOD_GET, '/inventory/entries/7-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('list', $json));
		$this->assertArrayHasKey('error', $json);

		// Richiesta filtrata
		$client->request(Request::METHOD_GET, '/inventory/entries/0-9?status=issued');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(2, $json['total']);

		// Richiesta con ordinamento
		$client->request(Request::METHOD_GET, '/inventory/entries/0-9/-quantity');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);

		$values = array_map(function($entity) { return $entity['quantity']; }, $json['list']);
		$sorted = $values;
		sort($sorted);
		$sorted = array_reverse($sorted);
		$this->assertEquals($values, $sorted);
	}

}