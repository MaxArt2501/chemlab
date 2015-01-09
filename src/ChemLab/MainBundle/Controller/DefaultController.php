<?php

namespace ChemLab\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller {
	public function indexAction(Request $request) {
		$data = '';
		// $data = $request->get('_route');
		// $data = $request->attributes->get('_route');
		// $data = json_encode($request->attributes->all());
		// $data = json_encode($this->container->getParameter('kernel.bundles'));
		// $data = json_encode($this->container->get('router')->getRouteCollection()->all());

		// $user = $this->getUser();
		// $data = empty($user) ? 'null' : get_class($user);

		// $metadata = $this->getDoctrine()->getManager()->getClassMetadata('ChemLabCatalogBundle:Item');
		// $props = $metadata->getReflectionProperties();
		// $data = json_encode($metadata);
		// $data = json_encode(array_map(function($prop) {
			// return $prop->getName();
		// }, $props));

		// $data = json_encode($request->query->all());

		$repo = $this->getDoctrine()->getRepository('ChemLabRequestBundle:Order');
		$query = $repo->createQueryBuilder('i')
				->setMaxResults(10)
				->orderBy('i.datetime', 'DESC')
				->getQuery();

		return $this->render('ChemLabMainBundle:Default:index.html.twig', array('data' => $data, 'orders' => $query->getResult() ));
	}
}
