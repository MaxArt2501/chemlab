<?php
namespace ChemLab\Utilities;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Classe astratta che fornisce metodi di utilità per il login all'applicazione
 */
class AuthWebTestCase extends WebTestCase {
	const LOGIN_PATH = '/login';
	const LOGOUT_PATH = '/logout';
	const AUTH_FIREWALL = 'main';
	const USER_CLASS = '\ChemLab\AccountBundle\Entity\User';

	/**
	 * @var \Symfony\Bundle\FrameworkBundle\Client
	 */
	protected $client;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $manager;

	public function setUp() {
		$this->client = static::createClient();
		$this->manager = $this->client->getContainer()->get('doctrine')->getManager();
	}

	/**
	 * Imposta il token di sessione per l'emulazione di un user loggato
	 * @param string $username    Nome utente (deve essere presente in DB)
	 * @param string [$firewall]  Firewall di sicurezza
	 * @return User
	 */
	protected function fakeLogin($username, $firewall = null) {
		$user = $this->manager
				->getRepository(static::USER_CLASS)
				->findOneByUsername($username);
		$this->assertTrue($user !== null);
		$this->assertEquals($username, $user->getUsername());

		if ($firewall === null)
			$firewall = static::AUTH_FIREWALL;

		$container = $this->client->getContainer();
		$token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
		$container->get('security.context')->setToken($token);

		$session = $container->get('session');
		$session->set('_security_'.$firewall, serialize($token));
		$session->save();

		$cookie = new Cookie($session->getName(), $session->getId());
		$this->client->getCookieJar()->set($cookie);

		$loggedUser = $this->getLoggedUser();
		$this->assertTrue($loggedUser !== null);
		$this->assertEquals($username, $loggedUser->getUsername());

		return $loggedUser;
	}

	/**
	 * Emula il logout di un utente rimuovendo il token di sessione
	 * @param string [$firewall]  Firewall di sicurezza
	 */
	protected function fakeLogout($firewall = null) {
		if ($firewall === null)
			$firewall = static::AUTH_FIREWALL;

		$container = $this->client->getContainer();
		$container->get('security.context')->setToken(null);
		$session = $container->get('session');
		$session->remove('_security_'.$firewall);

		$this->client->getCookieJar()->expire($session->getName());

		$this->assertNull($this->getLoggedUser());
	}

	/**
	 * Esegue il login di un utente compilando il form della pagina di login
	 * @param string $username    Nome utente (deve essere presente in DB)
	 * @param string $password    Password utente
	 * @return User
	 */
	protected function trueLogin($username, $password) {
		$client = $this->client;
		$crawler = $client->request('GET', static::LOGIN_PATH);

		$loginForm = $crawler->filter('form#loginForm');
		$this->assertCount(1, $loginForm);

		$client->submit($loginForm->form(), array(
			'_username' => $username,
			'_password' => $password
		));

		$this->assertTrue($client->getResponse()->isRedirect());
		$client->followRedirect();
		$this->assertTrue($client->getResponse()->isSuccessful());

		$loggedUser = $this->getLoggedUser();
		$this->assertTrue($loggedUser !== null);
		$this->assertEquals($username, $loggedUser->getUsername());

		return $loggedUser;
	}

	/**
	 * Effettua il logout dell'utente loggato chiamando l'url di logout.
	 * L'utente deve essere preventivamente loggato.
	 */
	protected function trueLogout() {
		$this->client->request('GET', static::LOGOUT_PATH);
		$this->assertTrue($this->client->getResponse()->isRedirect());

		$this->assertNull($this->getLoggedUser());
	}

	/**
	 * Restituisce l'attuale utente loggato, o null.
	 * @return User
	 */
	protected function getLoggedUser() {
		$token = $this->client->getContainer()->get('security.context')->getToken();
		return $token ? $token->getUser() : null;
	}
}