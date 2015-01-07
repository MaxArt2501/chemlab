<?php

namespace ChemLab\RequestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
		$doctrine = $this->getDoctrine();
		$data = array(
			'items' => $doctrine->getRepository('ChemLabCatalogBundle:Item')->findAll()
		);
		if ($this->get('security.context')->isGranted('ROLE_ADMIN'))
			$data['locations'] = $doctrine->getRepository('ChemLabLocationBundle:Location')->findAll();

		return $this->render('ChemLabRequestBundle:Default:index.html.twig', $data);
    }
}
