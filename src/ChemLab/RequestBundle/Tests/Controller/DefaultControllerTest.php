<?php
namespace ChemLab\RequestBundle\Tests\Controller;

use ChemLab\Utilities\AuthWebTestCase;
use Symfony\Component\HttpFoundation\Request;

class DefaultControllerTest extends AuthWebTestCase {

	public function testRequest() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/request');
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$form = $crawler->filter('#loginForm');
		$this->assertCount(1, $form);

		$user = $this->trueLogin('testuser', 'commonuser');

		$crawler = $client->request(Request::METHOD_GET, '/request');
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione ordini")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));

		$form = $crawler->filter('#entityModal form');
		$this->assertCount(1, $form);
		$this->assertCount(1, $form->filter('input#orderQuantity'));
	}

	public function testFakeRequest() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/request');
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$crawler = $client->request(Request::METHOD_GET, '/request');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione ordini")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));
	}

}
