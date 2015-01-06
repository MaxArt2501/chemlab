<?php

namespace ChemLab\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
    public function indexAction(Request $request) {
		$data = '';
        return $this->render('ChemLabMainBundle:Default:index.html.twig', array('data' => $data));
    }
}
