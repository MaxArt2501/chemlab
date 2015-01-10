<?php
namespace ChemLab\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('username', 'text', array(
				'label' => 'Nome utente', 'required' => true,
				'attr' => array( 'autofocus' => 'autofocus' )
			))
			->add('password', 'repeated', array(
				'type' => 'password',
				'invalid_message' => 'Le password devono coincidere',
				'required' => true,
				'first_options'  => array('label' => 'Nuova password'),
				'second_options' => array('label' => 'Conferma password')
			))
			->add('name', 'text', array( 'label' => 'Nome', 'required' => true ))
			->add('surname', 'text', array( 'label' => 'Cognome', 'required' => true ))
			->add('email', 'email')
			->add('gender', 'choice', array(
				'choices'   => array( 'N' => 'N/A', 'F' => 'Femmina', 'M' => 'Maschio' ),
				'required'  => true, 'label' => 'Sesso'
			))
			->add('accept', 'checkbox', array(
				'required'  => true, 'mapped' => false,
				'label' => 'Accetta i termini delle condizioni d\'uso'
			))
			->add('doregister', 'submit', array(
				'label' => 'Registrami',
				'attr' => array( 'class' => 'btn-primary' )
			));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'ChemLab\AccountBundle\Entity\User'
		));
	}

	public function getName() { return 'register'; }
}