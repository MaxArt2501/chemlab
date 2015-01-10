<?php
namespace ChemLab\AccountBundle\Form\Model;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePassword {
	/**
	 * @SecurityAssert\UserPassword(message = "La password corrente è errata")
	 */
	protected $oldPassword;

	/**
	 * @Assert\Length(min = 6, minMessage = "Lunghezza minima: {{ limit }} caratteri")
	 */
	protected $newPassword;

	public function getOldPassword() {
		return $this->oldPassword;
	}

	public function setOldPassword($oldPassword) {
		$this->oldPassword = $oldPassword;
	}

	public function getNewPassword() {
		return $this->newPassword;
	}

	public function setNewPassword($newPassword) {
		$this->newPassword = $newPassword;
	}
}