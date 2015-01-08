<?php

namespace ChemLab\AccountBundle\Controller;

use ChemLab\AccountBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
	public function profileAction(Request $request) {

		$user = $this->getUser();
		$form = $this->createFormBuilder($user)
				->add('name', 'text', array( 'label' => 'Nome', 'required' => true ))
				->add('surname', 'text', array( 'label' => 'Cognome', 'required' => true ))
				->add('email', 'email', array( 'required' => true ))
				->add('gender', 'choice', array(
					'choices'   => array( 'N' => 'N/A', 'M' => 'Maschio', 'F' => 'Femmina'),
					'required'  => true, 'label' => 'Sesso'
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
