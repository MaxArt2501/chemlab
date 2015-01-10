<?php

namespace ChemLab\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
	public function indexAction(Request $request) {
		$data = '';

		$repo = $this->getDoctrine()->getRepository('ChemLabRequestBundle:Order');
		$query = $repo->createQueryBuilder('i')
				->setMaxResults(10)
				->orderBy('i.datetime', 'DESC')
				->getQuery();

		return $this->render('ChemLabMainBundle:Default:index.html.twig', array('data' => $data, 'orders' => $query->getResult() ));
	}
}
