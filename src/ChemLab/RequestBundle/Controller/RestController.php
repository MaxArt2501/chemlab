<?php

namespace ChemLab\RequestBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabRequestBundle:Order';
	}

	protected function parseInput(array $input) {
		if ($this->getRequest()->getMethod() === \Symfony\Component\HttpFoundation\Request::METHOD_POST) {
			$input['owner'] = $this->getUser();
			$input['status'] = 'issued';
			if (is_numeric(@$input['item'])) {
				$input['item'] = $this->getDoctrine()
						->getRepository('ChemLabCatalogBundle:Item')
						->find(intval($input['item']));
			}
		} else {
			if (!$this->get('security.context')->isGranted('ROLE_ADMIN'))
				return null;

			if (array_keys($input) !== [ 'status' ])
				return null;
		}
		$input['datetime'] = new \DateTime();

		return $input;
	}

}
