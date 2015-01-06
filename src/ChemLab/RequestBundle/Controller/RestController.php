<?php

namespace ChemLab\RequestBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;
use ChemLab\CatalogBundle\Entity\Item;
use ChemLab\AccountBundle\Entity\User;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabRequestBundle:Order';
	}

	protected function parseInput(array $input) {
		foreach ($input as $key => $value) {
			if (is_integer($value)) {
				if ($key === 'item') {
					$input['item'] = $this->getDoctrine()
							->getRepository('ChemLabCatalogBundle:Item')
							->find($value);
				} elseif ($key === 'owner') {
					$input['owner'] = $this->getDoctrine()
							->getRepository('ChemLabAccountBundle:User')
							->find($value);
				}
			}
		}

		return $input;
	}

}
