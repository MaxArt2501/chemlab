<?php
namespace ChemLab\AccountBundle\DataFixtures\ORM;

use ChemLab\AccountBundle\Entity\User;
use ChemLab\Utilities\FixtureLoader;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUserData extends FixtureLoader {
	public function __construct() {
		$this->setEntityClass('\ChemLab\AccountBundle\Entity\User')
				->setOrder(1);
	}

	protected function dataProcess(array $data, ObjectManager $manager) {
		if (isset($data['password']) && $data['password'][0] !== '$') {
			$encfactory = $this->container->get('security.password_encoder');
			$data['password'] = $encfactory->encodePassword(new User(), $data['password']);
		}

		return $data;
	}
}