<?php

namespace ChemLab\AccountBundle\Tests\Controller;

use ChemLab\Utilities\AuthWebTestCase;
use Symfony\Component\HttpFoundation\Request;

class DefaultControllerTest extends AuthWebTestCase {

	public function testAccess() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/access');
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$form = $crawler->filter('#loginForm');
		$this->assertCount(1, $form);

		$user = $this->trueLogin('admin', 'theAdmin');

		$crawler = $client->request(Request::METHOD_GET, '/access');
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione utenti")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));

		$form = $crawler->filter('#entityModal form');
		$this->assertCount(1, $form);
		$this->assertCount(1, $form->filter('input#userUsername'));
	}

	public function testFakeAccess() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/access');
		$this->assertTrue($client->getResponse()->isRedirect());

		$this->fakeLogin('testuser');
		$client->request(Request::METHOD_GET, '/access');
		$this->assertTrue($client->getResponse()->isForbidden());

		$this->fakeLogin('admin');
		$crawler = $client->request(Request::METHOD_GET, '/access');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Gestione utenti")'));
		$this->assertCount(1, $crawler->filter('#tableOutput'));
	}

	public function testProfile() {
		$this->assertNull($this->getLoggedUser());

		$client = $this->client;
		$client->request(Request::METHOD_GET, '/profile');
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$form = $crawler->filter('#loginForm');
		$this->assertCount(1, $form);

		$user = $this->trueLogin('admin', 'theAdmin');

		$crawler = $client->request(Request::METHOD_GET, '/profile');
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Profilo personale")'));
		$this->assertCount(1, $crawler->filter('h3:contains("'.$user->getName().' '.$user->getSurname().'")'));

		$form = $crawler->filter('form');
		$this->assertCount(2, $form);

		$crawler = $client->submit($form->form());
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('.alert-success'));

		$form = $crawler->filter('form[name="pwdform"]');
		$this->assertCount(1, $form);

		$crawler = $client->submit($form->form(), array( 'pwdform' => array(
			'oldPassword' => 'theAdmin',
			'newPassword' => array( 'first' => 'theAdmin', 'second' => 'theAdmin' )
		)));
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('.alert-success'));
	}

}
