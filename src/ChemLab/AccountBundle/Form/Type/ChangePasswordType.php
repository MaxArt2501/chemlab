<?php
namespace ChemLab\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChangePasswordType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('oldPassword', 'password')
			->add('newPassword', 'repeated', array(
				'type' => 'password',
				'invalid_message' => 'Le password devono coincidere',
				'required' => true,
				'first_options'  => array('label' => 'Nuova password'),
				'second_options' => array('label' => 'Conferma password')
			))
			->add('pwdsave', 'submit', array(
				'label' => 'Cambia password',
				'attr' => array( 'class' => 'btn-primary' )
			));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'ChemLab\AccountBundle\Form\Model\ChangePassword',
		));
	}

	public function getName() {
		return 'pwdform';
	}
}