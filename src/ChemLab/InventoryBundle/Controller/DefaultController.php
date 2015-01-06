<?php

namespace ChemLab\InventoryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    public function indexAction() {
		$locs = $this->getDoctrine()
				->getRepository('ChemLabLocationBundle:Location')
				->findAll();

        return $this->render('ChemLabInventoryBundle:Default:index.html.twig', array( 'locations' => $locs ));
    }
}
