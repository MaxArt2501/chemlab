<?php

namespace ChemLab\CatalogBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabCatalogBundle:Item';
	}

}
