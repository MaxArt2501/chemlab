<?php
namespace ChemLab\MainBundle\Tests\Controller;

use ChemLab\Utilities\AuthWebTestCase;

class DefaultControllerTest extends AuthWebTestCase {

	public function testIndex() {
		$client = $this->client;
		$crawler = $client->request('GET', '/');

		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(1, $crawler->filter('h4'));
		$this->assertCount(0, $crawler->filter('#userWelcome'));
		$this->assertCount(2, $crawler->filter('nav.navbar ul.nav.navbar-nav'));
		$this->assertTrue($crawler->filter('a[href="'.static::LOGIN_PATH.'"]')->count() > 0);
		$this->assertCount(0, $crawler->filter('a[href="/inventory"]'));
		$this->assertCount(0, $crawler->filter('#lastOrders'));
	}

	public function testFakeLoginLogout() {
		$client = $this->client;

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$alink = $crawler->filter('a[href="'.static::LOGIN_PATH.'"]');
		$this->assertTrue($alink->count() > 0);

		$user = $this->fakeLogin('admin');

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(1, $crawler->filter('#lastOrders'));
		$this->assertCount(1, $crawler->filter('#userWelcome:contains("'.$user->getName().' '.$user->getSurname().'")'));
		$this->assertCount(0, $crawler->filter('a[href="'.static::LOGIN_PATH.'"]'));
		$this->assertCount(1, $crawler->filter('a[href="/inventory"]'));
		$this->assertCount(1, $crawler->filter('a[href="/profile"]'));

		$this->fakeLogout();

		$crawler = $client->request('GET', '/');
		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(0, $crawler->filter('#userWelcome'));
	}

	public function testTrueLoginLogout() {
		$client = $this->client;

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$alink = $crawler->filter('a[href="'.static::LOGIN_PATH.'"]');
		$this->assertTrue($alink->count() > 0);

		$user = $this->trueLogin('admin', 'theAdmin');

		$crawler = $client->request('GET', '/');
		$this->assertTrue($client->getResponse()->isSuccessful());

		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(1, $crawler->filter('#lastOrders'));
		$this->assertCount(1, $crawler->filter('#userWelcome:contains("'.$user->getName().' '.$user->getSurname().'")'));
		$this->assertCount(0, $crawler->filter('a[href="'.static::LOGIN_PATH.'"]'));
		$this->assertCount(1, $crawler->filter('a[href="/inventory"]'));
		$this->assertCount(1, $crawler->filter('a[href="/profile"]'));

		$this->trueLogout();

		$crawler = $client->request('GET', '/');
		$this->assertCount(1, $crawler->filter('#mainTitle'));
		$this->assertCount(0, $crawler->filter('#userWelcome'));
	}

}
