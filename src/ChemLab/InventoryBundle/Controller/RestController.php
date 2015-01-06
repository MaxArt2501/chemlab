<?php

namespace ChemLab\InventoryBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;
use ChemLab\CatalogBundle\Entity\Item;
use ChemLab\LocationBundle\Entity\Location;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabInventoryBundle:Entry';
	}

	protected function parseInput(array $input) {
		foreach ($input as $key => $value) {
			if (is_integer($value)) {
				if ($key === 'item') {
					$input['item'] = $this->getDoctrine()
							->getRepository('ChemLabCatalogBundle:Item')
							->find($value);
				} elseif ($key === 'item') {
					$input['location'] = $this->getDoctrine()
							->getRepository('ChemLabLocationBundle:Location')
							->find($value);
				}
			}
		}

		return $input;
	}

}
