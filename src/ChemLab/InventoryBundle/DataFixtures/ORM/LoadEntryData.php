<?php
namespace ChemLab\InventoryBundle\DataFixtures\ORM;

use ChemLab\Utilities\FixtureLoader;
use Doctrine\Common\Persistence\ObjectManager;

class LoadEntryData extends FixtureLoader {
	public function __construct() {
		$this->setEntityClass('\ChemLab\InventoryBundle\Entity\Entry')
				->setOrder(5);
	}

	protected function dataProcess(array $data, ObjectManager $manager) {
		if (is_string($data['location'])) {
			$repo = $manager->getRepository('ChemLabLocationBundle:Location');
			$loc = $repo->findOneByName($data['location']);
			$data['location'] = $loc;
		}

		if (is_string($data['item'])) {
			$repo = $manager->getRepository('ChemLabCatalogBundle:Item');
			$item = $repo->findOneByName($data['item']);
			$data['item'] = $item;
		}

		return $data;
	}
}