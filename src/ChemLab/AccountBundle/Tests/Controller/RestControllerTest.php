<?php
namespace ChemLab\AccountBundle\Tests\Controller;

use ChemLab\Utilities\RESTWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends RESTWebTestCase {

	public function setUp() {
		parent::setUp();
		$this->setEntityClass(static::USER_CLASS);
	}

	public function assertPreConditions() {

		$this->assertEquals(4, $this->getEntityCount());

		$this->entities = $users = $this->repository->findAll();

		$expectedUsers = array(
			'admin' => array( 'admin' => true, 'active' => true ),
			'testuser' => array( 'admin' => false, 'active' => true ),
			'thequeen' => array( 'admin' => true, 'active' => true ),
			'disableduser' => array( 'admin' => false, 'active' => false )
		);

		foreach ($users as $user) {
			$username = $user->getUsername();
			$this->assertArrayHasKey($user->getUsername(), $expectedUsers);
			$this->assertEquals($user->getAdmin(), $expectedUsers[$username]['admin']);
			$this->assertEquals($user->getActive(), $expectedUsers[$username]['active']);
		}
	}

	public function testGetUsers() {
		$this->assertCount(4, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$client->request(Request::METHOD_GET, "/access/users/$ids[0]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, "/access/users/$ids[0]");
		$this->assertTrue($client->getResponse()->isForbidden());

		// Richiesta non valida
		$this->fakeLogin('admin');
		$client->request(Request::METHOD_GET, '/access/users/admin');
		$this->assertTrue($client->getResponse()->isNotFound());

		$client->request(Request::METHOD_GET, "/access/users/$ids[0]");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals([ 'id', 'username', 'name', 'surname', 'email', 'gender', 'admin', 'active' ], array_keys($json));

		$this->assertTrue(is_integer($json['id']));
		foreach ([ 'username', 'name', 'surname', 'email', 'gender' ] as $prop)
			$this->assertTrue(is_string($json[$prop]));
		foreach ([ 'admin', 'active' ] as $prop)
			$this->assertTrue(is_bool($json[$prop]));

		$nonexistent = call_user_func_array('max', $ids) + 1;
		$client->request(Request::METHOD_GET, "/access/users/$nonexistent");
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('username', $json));
		$this->assertArrayHasKey('error', $json);
	}

	public function testPutPatchUsers() {
		$this->assertCount(4, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array( 'email' => $this->entities[0]->getEmail() ));
		$client->request(Request::METHOD_PATCH, "/access/users/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_PATCH, "/access/users/$ids[0]", [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');
		$client->request(Request::METHOD_PATCH, "/access/users/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

		$client->request(Request::METHOD_PUT, "/access/users/$ids[0]", [], [], [], $data);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
		$this->fakeLogout();
	}

	public function testPostDeleteUsers() {
		$this->assertCount(4, $this->entities);
		$ids = array_map(function($entity) { return $entity->getId(); }, $this->entities);

		$client = $this->client;

		$data = json_encode(array(
			'username' => 'newuser',
			'name' => 'John',
			'surname' => 'Doe',
			'email' => 'john.doe@email.com',
			'password' => 'correct horse battery staple',
			'gender' => 'M'
		));
		$invalid = json_encode(array(
			'username' => 'newuser',
			'email' => 'wrong.email',
			'password' => 'correct horse battery staple',
			'gender' => 'N'
		));

		$client->request(Request::METHOD_POST, '/access/users/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isRedirect());

		$client->request(Request::METHOD_DELETE, "/access/users/$ids[3]");
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_POST, '/access/users/0', [], [], [], $data);
		$this->assertTrue($client->getResponse()->isForbidden());

		$client->request(Request::METHOD_DELETE, "/access/users/$ids[3]");
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');

		// Inserimento dati non validi
		$client->request(Request::METHOD_POST, '/access/users/0', [], [], [], $invalid);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
		$this->assertArrayHasKey('fields', $json);
		$this->assertEquals([ 'name', 'surname', 'email' ], array_keys($json['fields']));

		// Inserimento dati corretti
		$client->request(Request::METHOD_POST, '/access/users/0', [], [], [], $data);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newuser = $this->repository->findOneByUsername('newuser');
		$this->assertTrue($newuser !== null);
		$this->assertEquals('john.doe@email.com', $newuser->getEmail());

		$newid = $newuser->getId();
		$client->request(Request::METHOD_DELETE, "/access/users/$newid");
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		// Cancellazione utente non esistente
		$client->request(Request::METHOD_DELETE, "/access/users/$newid");
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);
	}

	public function testList() {
		$client = $this->client;

		$client->request(Request::METHOD_GET, '/access/users/0-2');
		$this->assertTrue($client->getResponse()->isRedirect());

		// Gli utenti comuni non possono accedere al servizio
		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/access/users/0-2');
		$this->assertTrue($client->getResponse()->isForbidden());
		$this->fakeLogout();

		$this->fakeLogin('thequeen');
		$client->request(Request::METHOD_GET, '/access/users/0-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);
		$this->assertArrayHasKey('total', $json);

		// Richiesta non formalmente valida
		$client->request(Request::METHOD_GET, '/access/users/-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isNotFound());

		// Richiesta formalmente valida ma errata
		$client->request(Request::METHOD_GET, '/access/users/7-2');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertFalse(array_key_exists('list', $json));
		$this->assertArrayHasKey('error', $json);

		// Richiesta filtrata
		$client->request(Request::METHOD_GET, '/access/users/0-9?admin=1');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(2, $json['total']);

		// Richiesta con ordinamento
		$client->request(Request::METHOD_GET, '/access/users/0-9/-username');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('list', $json);

		$usernames = array_map(function($entity) { return $entity['username']; }, $json['list']);
		$sorted = $usernames;
		sort($sorted);
		$sorted = array_reverse($sorted);
		$this->assertEquals($usernames, $sorted);

		// Richiesta con filtro
		$client->request(Request::METHOD_GET, '/access/users/0-9?admin=1');
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());

		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertEquals(2, $json['total']);
		$this->fakeLogout();
	}
}