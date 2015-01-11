<?php
namespace ChemLab\InventoryBundle\Tests\Controller;

use ChemLab\Utilities\AuthWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends AuthWebTestCase {

	public function testRequest() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/inventory');
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$form = $crawler->filter('#loginForm');
		$this->assertCount(1, $form);

		$user = $this->trueLogin('testuser', 'commonuser');

		$crawler = $client->request(Request::METHOD_GET, '/inventory');
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione inventario")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));

		$form = $crawler->filter('#entityModal form');
		$this->assertCount(1, $form);
		$this->assertCount(1, $form->filter('input#entryQuantity'));
	}

	public function testFakeRequest() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/inventory');
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$crawler = $client->request(Request::METHOD_GET, '/inventory');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione inventario")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));
	}

	public function testTransfer() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$items = $this->manager->getRepository('ChemLabCatalogBundle:Item')->findAll();
		$locs = $this->manager->getRepository('ChemLabLocationBundle:Location')->findAll();
		$entries = $this->manager->getRepository('ChemLabInventoryBundle:Entry')->findAll();
		$this->assertTrue(count($entries) > 0);

		$newentrydata = json_encode(array(
			'item' => $items[1]->getId(),
			'location' => $locs[0]->getId(),
			'quantity' => 2501
		));
		$oldid = $entries[0]->getId();
		$oldquantity = $entries[0]->getQuantity();
		$oldentrydata = json_encode(array(
			'item' => $entries[0]->getItem()->getId(),
			'location' => $entries[0]->getLocation()->getId(),
			'quantity' => 5
		));

		$client->request(Request::METHOD_POST, '/inventory/entries/transfer', [], [], [], $newentrydata);
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_POST, '/inventory/entries/transfer', [], [], [], $newentrydata);
		$response = $client->getResponse();
		$this->assertTrue($response->isSuccessful());
		$this->assertEquals('application/json', $response->headers->get('content-type'));
		$json = json_decode($response->getContent(), true);
		$this->assertArrayHasKey('error', $json);

		$this->fakeLogin('admin');
		$client->request(Request::METHOD_POST, '/inventory/entries/transfer', [], [], [], $newentrydata);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$newentry = $this->manager->getRepository('ChemLabInventoryBundle:Entry')
				->findOneByQuantity(2501);
		$this->assertTrue($newentry !== null);
		$this->manager->remove($newentry);
		$this->manager->flush();

		$client->request(Request::METHOD_POST, '/inventory/entries/transfer', [], [], [], $oldentrydata);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

		$oldentry = $this->manager->getRepository('ChemLabInventoryBundle:Entry')->findOneById($oldid);
		$this->assertTrue($oldentry !== null);
		$this->assertEquals($oldid, $oldentry->getId());
		$oldentry->setQuantity($oldquantity);
		$this->manager->persist($oldentry);
		$this->manager->flush();
	}
}
