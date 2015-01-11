<?php
namespace ChemLab\RequestBundle\Tests\Controller;

use ChemLab\RequestBundle\Entity\Order;
use ChemLab\Utilities\RESTWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestControllerTest extends RESTWebTestCase {
	const USER_CLASS = '\ChemLab\AccountBundle\Entity\User';
	const ITEM_CLASS = '\ChemLab\CatalogBundle\Entity\Item';

	/**
	 * @var \ChemLab\CatalogBundle\Entity\Item[]
	 */
	protected $items;

	/**
	 * @var \ChemLab\AccountBundle\Entity\User[]
	 */
	protected $users;

	public function setUp() {
		parent::setUp();
		$this->setEntityClass('\ChemLab\RequestBundle\Entity\Order');

		$this->items = $this->manager->getRepository(static::ITEM_CLASS)->findAll();
		$this->users = $this->manager->getRepository(static::USER_CLASS)->findAll();
	}

	public function assertPreConditions() {

		$this->assertEquals(2, $this->getEntityCount());
		$this->assertEquals(4, $this->getEntityCount(null, static::USER_CLASS));
		$this->assertEquals(2, $this->getEntityCount(null, static::ITEM_CLASS));

		$this->entities = $this->repository->findAll();

	}

	public function testGetEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$client->request(Request::METHOD_GET, "/request/orders/$ids[0]");
		$this->assertTrue($client->getResponse()->isRedirect());

		// Richiesta non valida
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/request/orders/Armadio A');
		$this->assertTrue($client->getResponse()->isNotFound());

		$client->request(Request::METHOD_GET, "/request/orders/$ids[0]");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals([ 'id', 'item', 'owner', 'quantity', 'total', 'status', 'datetime' ], array_keys($json));

		foreach ([ 'id', 'quantity', 'total' ] as $prop)
			$this->assertTrue(is_numeric($json[$prop]));
		foreach ([ 'status', 'datetime' ] as $prop)
			$this->assertTrue(is_string($json[$prop]));
		foreach ([ 'item', 'owner' ] as $prop)
			$this->assertTrue(is_array($json[$prop]));

		$nonexistent = call_user_func_array('max', $ids) + 1;
		$client->request(Request::METHOD_GET, "/request/orders/$nonexistent");
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

		$data = json_encode(array( 'status' => $this->entities[0]->getStatus() ));
		$invalid = json_encode(array( 'quantity' => $this->entities[0]->getQuantity() ));

		$client->request(Request::METHOD_PATCH, "/request/orders/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti normali non possono fare PUT/PATCH
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_PATCH, "/request/orders/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);

		$this->fakeLogin('admin');
		// Neanche gli amministratori possono cambiare parametri che non siano lo stato
		$client->request(Request::METHOD_PATCH, "/request/orders/$ids[0]", [], [], [], $invalid);
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);

		$client->request(Request::METHOD_PATCH, "/request/orders/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

		$client->request(Request::METHOD_PUT, "/request/orders/$ids[0]", [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
		$this->fakeLogout();
	}

	public function testPostDeleteEntities() {
		$this->assertCount(2, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array(
			'item' => $this->items[0]->getId(),
			'quantity' => 10,
			'total' => 123
		));
		$invalid = json_encode(array(
			'item' => $this->items[0]->getId()
		));

		$client->request(Request::METHOD_POST, '/request/orders/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$client->request(Request::METHOD_DELETE, "/request/orders/$ids[1]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		// Gli utenti comuni possono fare POST
		$client->request(Request::METHOD_POST, '/request/orders/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// ... ma non DELETE
		$client->request(Request::METHOD_DELETE, "/request/orders/$ids[1]");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);

		$this->fakeLogin('admin');

		// Inserimento dati non validi
		$client->request(Request::METHOD_POST, '/request/orders/0', [], [], [], $invalid);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
		$this->assertArrayHasKey('fields', $json);
		$this->assertEquals([ 'quantity', 'total' ], array_keys($json['fields']));

		// Cancellazione entità precedente
		$newentity = $this->repository->findOneByTotal(123);
		$this->assertTrue($newentity !== null);
		$this->assertEquals(10, $newentity->getQuantity());

		$newid = $newentity->getId();
		$client->request(Request::METHOD_DELETE, "/request/orders/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Reinserimento dati corretti
		$client->request(Request::METHOD_POST, '/request/orders/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newentity = $this->repository->findOneByTotal(123);
		$this->assertTrue($newentity !== null);
		$this->assertEquals(10, $newentity->getQuantity());

		$newid = $newentity->getId();
		$client->request(Request::METHOD_DELETE, "/request/orders/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Cancellazione utente non esistente
		$client->request(Request::METHOD_DELETE, "/request/orders/$newid");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
	}

	public function testList() {
		$client = $this->client;

		$client->request(Request::METHOD_GET, '/request/orders/0-2');
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti comuni possono accedere al servizio
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/request/orders/0-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);
		$this->assertArrayHasKey('total', $json);

		// Richiesta non formalmente valida
		$client->request(Request::METHOD_GET, '/request/orders/-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isNotFound());

		// Richiesta formalmente valida ma errata
		$client->request(Request::METHOD_GET, '/request/orders/7-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('list', $json));
		$this->assertArrayHasKey('error', $json);

		// Richiesta filtrata
		$client->request(Request::METHOD_GET, '/request/orders/0-9?status=issued');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(1, $json['total']);

		// Richiesta con ordinamento
		$client->request(Request::METHOD_GET, '/request/orders/0-9/-quantity');
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