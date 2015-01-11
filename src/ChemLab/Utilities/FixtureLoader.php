<?php
namespace ChemLab\Utilities;

use ChemLab\AccountBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Classe astratta per il caricamento delle fixtures per l'applicazione da file JSON.
 *
 * Il file viene cercato nella directory superiore a quella della classe che estende
 * FixtureLoader, con nome pari a quello semplice dell'entità (cioè "User" per
 * "Acme\UserBundle\Entity\User"), seguito da underscore, seguito del nome
 * dell'ambiente di esecuzione, e con estensione "json".
 * Ad esempio, per la classe Acme\UserBundle\DataFixtures\ORM\LoadUsers.php e
 * ambiente "dev", si cerca il file src/Acme/UserBundle/DataFixtures/User_dev.json
 *
 * Nell'implementazione, definire un costruttore per impostare $entityClass e $order.
 * @example
 * public function __construct() {
 *     $this->setEntityClass('\Acme\UserBundle\Entity\User')
 *          ->setOrder(1);
 * }
 */
abstract class FixtureLoader extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {
	/**
	 * @var integer
	 */
	protected $order;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var string
	 */
	protected $entityClass;

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container = null) {
		$this->container = $container;
	}

	/**
	 * Imposta l'ordine di esecuzione del caricamento dati
	 *
	 * @param integer $order
	 * @return FixtureLoader
	 */
	protected function setOrder($order) {
		$this->order = $order;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOrder() {
		return $this->order;
	}

	/**
	 * Imposta la classe dell'entità di riferimento
	 *
	 * @param string $class
	 * @return FixtureLoader
	 */
	protected function setEntityClass($class) {
		$this->entityClass = $class;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function load(ObjectManager $manager) {
		$kernel = $this->container->get('kernel');
		$root = $kernel->getRootDir();
		$env = $kernel->getEnvironment();

		$filename = basename($this->entityClass).'_'.$env.'.json';
		$path = realpath($root.'/../src/'.dirname(get_class($this)).'/../'.$filename);
		$rawdata = file_get_contents($path);

		if ($rawdata === null)
			throw new \Exception('Errore nel caricamento dati');

		$entities = json_decode($rawdata, true);
		if ($entities === null)
			throw new \Exception('Errore nel formato dati: '.json_last_error_msg());

		$encfactory = $this->container->get('security.password_encoder');

		$class = $this->entityClass;
		foreach ($entities as $entityData) {
			$entityData = $this->dataProcess($entityData, $manager);
			$entity = new $class();
			$entity->fromArray($entityData);
			$manager->persist($entity);
		}
		$manager->flush();

	}

	/**
	 * Funzione per override di processamento dati. Da definire per impostare
	 * i dati corretti per l'importazione nell'entità. Ad esempio, se nei file
	 * di dati ci fosse una password in chiaro, dataProcess potrebbe occuparsi
	 * di criptarla per inserirla nell'entità, che poi andrà in persistenza.
	 *
	 * @param array $data             Array di dati per l'entità
	 * @param ObjectManager $manager
	 * @return array
	 */
	protected function dataProcess(array $data, ObjectManager $manager) {
		return $data;
	}
}