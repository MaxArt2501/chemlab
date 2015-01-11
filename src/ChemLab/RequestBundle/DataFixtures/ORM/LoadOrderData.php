<?php
namespace ChemLab\RequestBundle\DataFixtures\ORM;

use ChemLab\Utilities\FixtureLoader;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOrderData extends FixtureLoader {
	public function __construct() {
		$this->setEntityClass('\ChemLab\RequestBundle\Entity\Order')
				->setOrder(4);
	}

	protected function dataProcess(array $data, ObjectManager $manager) {
		if (is_string($data['owner'])) {
			$repo = $manager->getRepository('ChemLabAccountBundle:User');
			$user = $repo->findOneByUsername($data['owner']);
			$data['owner'] = $user;
		}

		if (is_string($data['item'])) {
			$repo = $manager->getRepository('ChemLabCatalogBundle:Item');
			$item = $repo->findOneByName($data['item']);
			$data['item'] = $item;
		}

		if (@is_string($data['datetime']))
			$data['datetime'] = new \DateTime($data['datetime']);
		else $data['datetime'] = new \DateTime();

		return $data;
	}
}