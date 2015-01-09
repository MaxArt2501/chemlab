<?php

namespace ChemLab\AccountBundle\Controller;

use ChemLab\AccountBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {

	public function indexAction() {
		return $this->render('ChemLabAccountBundle:Default:index.html.twig');
	}

	public function profileAction(Request $request) {

		$user = $this->getUser();
		$form = $this->createFormBuilder($user)
				->add('name', 'text', array( 'label' => 'Nome', 'required' => true ))
				->add('surname', 'text', array( 'label' => 'Cognome', 'required' => true ))
				->add('email', 'email', array( 'required' => true ))
				->add('gender', 'choice', array(
					'choices'   => array( 'N' => 'N/A', 'F' => 'Femmina', 'M' => 'Maschio' ),
					'required'  => true, 'label' => 'Sesso'
				))
				->add('password', 'repeated', array(
					'type' => 'password',
					'invalid_message' => 'Le password non coincidono',
					'first_options'  => array('label' => 'Nuova password'),
					'second_options' => array('label' => 'Ripeti password')
				))
				->add('save', 'submit', array('label' => 'Imposta profilo', 'attr' => array( 'class' => 'btn-primary' )))
				->getForm();

		$form->handleRequest($request);

		if ($form->isValid()) {
			$manager = $this->getDoctrine()->getManager();
			$manager->persist($user);
			$manager->flush();

			return $this->redirect($this->generateUrl('chem_lab_main_homepage'));
		}

		return $this->render('ChemLabAccountBundle:Default:profile.html.twig', array( 'form' => $form->createView() ));
	}

}
