<?php

namespace ChemLab\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
    public function indexAction(Request $request) {
		// $data = $request->get('_route');
		// $data = $request->attributes->get('_route');
		// $data = json_encode($request->attributes->all());
		$data = json_encode($this->container->getParameter('kernel.bundles'));
		// $data = json_encode($this->container->get('router')->getRouteCollection()->all());
        return $this->render('ChemLabMainBundle:Default:index.html.twig', array('data' => $data));
    }
}
