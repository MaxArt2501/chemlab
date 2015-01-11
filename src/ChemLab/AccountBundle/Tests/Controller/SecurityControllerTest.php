<?php
namespace ChemLab\CatalogBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class SecurityControllerTest extends WebTestCase {
	/**
	 * @var \Symfony\Bundle\FrameworkBundle\Client
	 */
	protected $client;

	public function setUp() {
		$this->client = static::createClient();
	}

	public function testLogin() {
		// Utenti di test:
		// admin:theAdmin (admin)
		// testuser:commonuser
		// thequeen:OffWithTheHead (admin)
		// disableduser:lousypassword (non attivo)
		$client = $this->client;

		$crawler = $client->request(Request::METHOD_GET, '/login');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(0, $crawler->filter('.alert'));

		// Login utente inesistente
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

		// Login utente non attivo
		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array( '_username' => 'disableduser', '_password' => 'lousypassword' ));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(1, $crawler->filter('.alert-danger:contains("Utenza non attiva")'));

		// Login utente con amministratore
		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array( '_username' => 'admin', '_password' => 'theAdmin' ));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("ChemLab")'));
		$this->assertCount(1, $crawler->filter('h4'));
		$this->assertCount(1, $crawler->filter('#userWelcome'));

		// Verifica login
		$crawler = $client->request(Request::METHOD_GET, '/login');

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(1, $crawler->filter('#loggedAs'));
	}

	public function testRegister() {
		$client = $this->client;

		// Apertura pagina registrazione
		$crawler = $client->request(Request::METHOD_GET, '/register');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Registrazione")'));
		$form = $crawler->filter('form');
		$this->assertCount(1, $form);
		$this->assertCount(1, $crawler->selectLink('login'));

		// Registrazione non valida
		$crawler = $client->submit($form->form(), array( 'register' => array(
			'username' => 'newuser',
			'password' => array( 'first' => 'loremipsum', 'second' => 'aaa' ),
			'name' => '', 'surname' => '',
			'email' => 'newuser@email.com',
			'gender' => 'N'
		)));
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(4, $crawler->filter('.has-error'));

		// Registrazione valida
		$form = $crawler->filter('form');
		$this->assertCount(1, $form);
		$crawler = $client->submit($form->form(), array( 'register' => array(
			'username' => 'newuser',
			'password' => array( 'first' => 'loremipsum', 'second' => 'loremipsum' ),
			'name' => 'Name', 'surname' => 'Surname',
			'email' => 'newuser@email.com',
			'gender' => 'N', 'accept' => 1
		)));
		$this->assertTrue($client->getResponse()->isRedirect());

		// Login con nuovo utente
		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("Login")'));
		$this->assertCount(0, $crawler->filter('#loggedAs'));
		$this->assertCount(1, $crawler->filter('.alert-success'));

		$form = $crawler->filter('#loginForm');
		$client->submit($form->form(), array( '_username' => 'newuser', '_password' => 'loremipsum' ));
		$this->assertTrue($client->getResponse()->isRedirect());

		$crawler = $client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());
		$this->assertCount(1, $crawler->filter('#mainTitle:contains("ChemLab")'));
		$this->assertCount(1, $crawler->filter('h4'));
		$this->assertCount(1, $crawler->filter('#userWelcome'));

		// Verifica utente loggato
		$token = $this->client->getContainer()->get('security.context')->getToken();
		$user = $token ? $token->getUser() : null;
		$this->assertTrue($user !== null);
		$this->assertEquals('newuser', $user->getUsername());

		// Logout
		$this->client->request(Request::METHOD_GET, '/logout');
		$this->assertTrue($this->client->getResponse()->isRedirect());

		// Eliminazione utente registrato
		$manager = $this->client->getContainer()->get('doctrine')->getManager();
		$repository = $manager->getRepository('ChemLabAccountBundle:User');
		$user = $repository->findOneByUsername('newuser');
		$manager->remove($user);
		$manager->flush();

		// Verifica cancellazione
		$user = $repository->findOneByUsername('newuser');
		$this->assertNull($user);
	}
}
