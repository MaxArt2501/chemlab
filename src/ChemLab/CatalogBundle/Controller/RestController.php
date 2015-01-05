<?php

namespace ChemLab\CatalogBundle\Controller;

use ChemLab\CatalogBundle\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RestController extends Controller {

    public function mainAction($id, Request $request) {
		$method = $request->getMethod();

		$response = new Response('', 200, array( 'content-type' => 'application/json' ));

		switch ($method) {

			case Request::METHOD_GET:
				$item = $this->getItem($id);
				if ($item)
					$retobj = $item->toArray();
				else $retobj = array( 'error' => 'Oggetto non trovato' );
				break;

			case Request::METHOD_PUT:
			case Request::METHOD_PATCH:
			case Request::METHOD_POST:
				$data = $request->getContent();
				$input = json_decode($data, true);

				$error = json_last_error();
				if ($error !== JSON_ERROR_NONE) {
					$retobj = array( 'error' => "Input malformato ($error)" );
					break;
				}

				$check = $this->checkData($input, $method !== Request::METHOD_PATCH);
				if ($check) {
					$retobj = array( 'error' => "Input non corretto: $check" );
					break;
				}

				if ($method === Request::METHOD_POST) {
					$item = new Item();
				} else {
					$item = $this->getItem($id);
					if (!$item) {
						$retobj = array( 'error' => "Oggetto non trovato" );
						break;
					}
				}

				foreach (array( 'name', 'description', 'code', 'type', 'price' ) as $key)
					if (array_key_exists($key, $input))
						call_user_func(array( $item, 'set'.ucfirst($key) ), $input[$key]);

				$manager = $this->getDoctrine()->getManager();
				$manager->persist($item);
				$manager->flush();

				$response->setStatusCode(204);

				return $response;

			case Request::METHOD_DELETE:
				$item = $this->getItem($id);
				if ($item) {

					$manager = $this->getDoctrine()->getManager();
					$manager->remove($item);
					$manager->flush();
					$response->setStatusCode(204);

					return $response;

				} else $retobj = array( 'error' => 'Oggetto non trovato' );
				break;
		}

		$response->setContent(json_encode($retobj));

        return $response;
    }

	public function listAction($start, $end, $sort) {
		$start = intval($start);
		$end = intval($end);

		$response = new Response('', 200, array( 'content-type' => 'application/json' ));

		if ($start > $end) {
			$response->setContent(json_encode(array( 'error' => 'Selezione non valida' )));
			return $response;
		}

		$repo = $this->getDoctrine()->getRepository('ChemLabCatalogBundle:Item');
		$qb = $repo->createQueryBuilder('i')
				->setFirstResult($start)
				->setMaxResults($end - $start + 1);
		if (!empty($sort))
			$qb->orderBy('i.'.substr($sort, 1), $sort[0] === '+' ? 'ASC' : 'DESC');
		$query = $qb->getQuery();

		$items = $query->getResult();

		$list = array();
		foreach ($items as $item)
			$list[] = $item->toArray();

		$qb = $repo->createQueryBuilder('i');
		$query = $qb->select($qb->expr()->count('i.id'))->getQuery();
		$total = $query->getSingleResult();

		$response->setContent(json_encode(array(
			'list' => $list,
			'total' => intval($total[1])
		)));

        return $response;
	}

	/**
	 * @param string $id  ID dell'oggetto Item da trovare
	 * @return Item
	 */
	private function getItem($id) {
		return $this->getDoctrine()
			->getRepository('ChemLabCatalogBundle:Item')
			->find($id);
	}

	/**
	 * Valida la correttezza e la completezza dei dati di input
	 * @param array $data    Oggetto JSON da controllare
	 * $param boolean $full  Verifica che siano presenti tutte le chiavi necessarie
	 * @return string
	 */
	private function checkData($data, $full = false) {
		if (!$data) return 'nodata';
		
		foreach (array( 'name', 'description', 'code', 'type' ) as $key) {
			if ($full && !array_key_exists($key, $data)) return $key;
			if (!is_string($data[$key])) return $key;
		}

		if ($full && !array_key_exists('price', $data) || !is_numeric($data['price']))
			return 'price';

		return '';
	}
}
