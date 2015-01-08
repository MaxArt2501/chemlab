<?php

namespace ChemLab\InventoryBundle\Controller;

use ChemLab\InventoryBundle\Entity\Entry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {
    public function indexAction() {
		$items = $this->getDoctrine()
				->getRepository('ChemLabCatalogBundle:Item')
				->findAll();
		$locs = $this->getDoctrine()
				->getRepository('ChemLabLocationBundle:Location')
				->findAll();

        return $this->render('ChemLabInventoryBundle:Default:index.html.twig', array( 'items' => $items, 'locations' => $locs ));
    }

	public function transferAction(Request $request) {
		$response = new Response('', 204);
		$data = $request->getContent();
		$input = json_decode($data, true);

		$error = json_last_error();
		if ($error !== JSON_ERROR_NONE)
			$retobj = array( 'error' => "Input malformato ($error)" );

		if (!isset($retobj))
			foreach ([ 'item', 'location', 'quantity' ] as $key)
				if (!array_key_exists($key, $input) || !is_integer($input[$key]) || $input[$key] < 1) {
					$retobj = array( 'error' => "Input non corretto ($key)" );
					break;
				}

		if (!isset($retobj)) {
			$doctrine = $this->getDoctrine();
			$item = $doctrine->getRepository("ChemLabCatalogBundle:Item")->find($input['item']);
			if (!$item) $retobj = array( 'error' => "Articolo non trovato" );
		}
		if (!isset($retobj)) {
			$loc = $doctrine->getRepository("ChemLabLocationBundle:Location")->find($input['location']);
			if (!$loc) $retobj = array( 'error' => "Locazione non definita" );
		}
		if (!isset($retobj)) {
			$entry = $doctrine->getRepository("ChemLabInventoryBundle:Entry")
					->findOneBy(array( 'item' => $input['item'], 'location' => $input['location']));
			if ($entry) {
				$quantity = $entry->getQuantity() + $input['quantity'];
				$entry->setQuantity($quantity);
			} else {
				$quantity = $input['quantity'];
				$entry = new Entry();
				$entry->fromArray(array(
					'item' => $item,
					'location' => $loc,
					'quantity' => $quantity,
					'notes' => 'Inserimento automatico da ordine'
				));
			}
			$manager = $doctrine->getManager();
			$manager->persist($entry);
			$manager->flush();
		}

		if (isset($retobj))
			$response->setStatusCode(200)
					->setContent(json_encode($retobj))
					->headers->add(array( 'content-type' => 'application/json' ));

		return $response;
	}
}
