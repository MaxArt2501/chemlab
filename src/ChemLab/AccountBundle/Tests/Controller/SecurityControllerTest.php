<?php
namespace ChemLab\CatalogBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase {
    public function testLogin() {
		// Utenti di test:
		// admin:theAdmin
		// testuser:commonuser
		// thequeen:OffWithTheHead
		// disableduser:lousypassword
		$client = static::createClient();

		$crawler = $client->request('GET', '/login');

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(0, $crawler->filter('.alert'));

		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array(
			'_username' => 'nonexistentuser',
			'_password' => 'correcthorsebatterystaple'
		));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(1, $crawler->filter('.alert-danger:contains("Credenziali errate")'));

		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array( '_username' => 'disableduser', '_password' => 'lousypassword' ));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(1, $crawler->filter('.alert-danger:contains("Utenza non attiva")'));

		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array( '_username' => 'admin', '_password' => 'theAdmin' ));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("ChemLab")'));
		$this->assertCount(1, $crawler->filter('h4'));
		$this->assertCount(1, $crawler->filter('#userWelcome'));

		$crawler = $client->request('GET', '/login');

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(1, $crawler->filter('#loggedAs'));
    }
}
