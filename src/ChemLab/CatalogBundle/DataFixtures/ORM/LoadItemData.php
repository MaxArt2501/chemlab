<?php
namespace ChemLab\CatalogBundle\DataFixtures\ORM;

use ChemLab\Utilities\FixtureLoader;

class LoadItemData extends FixtureLoader {
	public function __construct() {
		$this->setEntityClass('\ChemLab\CatalogBundle\Entity\Item')
				->setOrder(3);
	}
}