<?php
namespace ChemLab\CatalogBundle\Tests\Controller;

use ChemLab\Utilities\AuthWebTestCase;
use Symfony\Component\HttpFoundation\Request;

class DefaultControllerTest extends AuthWebTestCase {

	public function testCatalog() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/catalog');
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$form = $crawler->filter('#loginForm');
		$this->assertCount(1, $form);

		$user = $this->trueLogin('admin', 'theAdmin');

		$crawler = $client->request(Request::METHOD_GET, '/catalog');
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione catalogo")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));

		$form = $crawler->filter('#entityModal form');
		$this->assertCount(1, $form);
		$this->assertCount(1, $form->filter('input#itemName'));
	}

	public function testFakeCatalog() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/catalog');
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/catalog');
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');
		$crawler = $client->request(Request::METHOD_GET, '/catalog');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione catalogo")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));
	}

}
