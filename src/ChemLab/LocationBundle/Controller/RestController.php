<?php

namespace ChemLab\LocationBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabLocationBundle:Location';
	}

}
