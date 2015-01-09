<?php

namespace ChemLab\MainBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

define('LOGIN_PATH', '/login');
define('AUTH_FIREWALL', 'secured_area');

class DefaultControllerTest extends WebTestCase {
	/**
	 * @var \Symfony\Bundle\FrameworkBundle\Client
	 */
	private $client;

	private $manager;

	public function setUp() {
		$this->client = static::createClient();
		$this->manager = $this->client->getContainer()->get('doctrine')->getManager();
	}

	public function testIndex() {
		$client = $this->client;
		$crawler = $client->request('GET', '/');

		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(1, $crawler->filter('h4'));
		$this->assertCount(0, $crawler->filter('#userWelcome'));
		$this->assertCount(2, $crawler->filter('nav.navbar ul.nav.navbar-nav'));
		$this->assertCount(1, $crawler->filter('a[href="'.LOGIN_PATH.'"]'));
		$this->assertCount(0, $crawler->filter('a[href="/inventory"]'));
		$this->assertCount(1, $crawler->filter('#lastOrders'));
	}

	public function testLoginLogout() {
		$client = $this->client;

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$alink = $crawler->filter('a[href="'.LOGIN_PATH.'"]');
		$this->assertCount(1, $alink);

		$user = $this->manager
				->getRepository('ChemLabAccountBundle:User')
				->findOneByUsername('admin');
		$this->assertTrue($user !== null);
		$this->assertEquals('admin', $user->getUsername());

		$container = $client->getContainer();
		$token = new UsernamePasswordToken($user, null, AUTH_FIREWALL, $user->getRoles());
		$container->get('security.context')->setToken($token);

		$session = $container->get('session');
		$session->set('_security_'.AUTH_FIREWALL, serialize($token));
		$session->save();

		$cookie = new Cookie($session->getName(), $session->getId());
		$this->client->getCookieJar()->set($cookie);

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(1, $crawler->filter('#lastOrders'));
		$this->assertCount(1, $crawler->filter('#userWelcome'));
		$this->assertCount(0, $crawler->filter('a[href="'.LOGIN_PATH.'"]'));
		$this->assertCount(1, $crawler->filter('a[href="/inventory"]'));
		$this->assertCount(1, $crawler->filter('a[href="/profile"]'));

		$crawler = $client->request('GET', '/logout');
		$this->assertTrue($client->getResponse()->isRedirect());
	}
}
