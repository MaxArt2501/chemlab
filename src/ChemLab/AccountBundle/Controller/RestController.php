<?php

namespace ChemLab\AccountBundle\Controller;

use ChemLab\Utilities\SimpleRESTController;
use ChemLab\AccountBundle\Entity\User;

class RestController extends SimpleRESTController {

	public function __construct() {
		$this->repository = 'ChemLabAccountBundle:User';
	}

	protected function parseInput(array $input) {
		if (isset($input['password'])) {
			if (trim($input['password']) === '')
				unset($input['password']);
			else
				$input['password'] = $this->container->get('security.password_encoder')
						->encodePassword(new User(), $input['password']);
		}

		return $input;
	}

}
