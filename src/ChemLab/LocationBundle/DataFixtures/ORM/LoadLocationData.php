<?php
namespace ChemLab\LocationBundle\DataFixtures\ORM;

use ChemLab\Utilities\FixtureLoader;

class LoadLocationData extends FixtureLoader {
	public function __construct() {
		$this->setEntityClass('\ChemLab\LocationBundle\Entity\Location')
				->setOrder(2);
	}
}