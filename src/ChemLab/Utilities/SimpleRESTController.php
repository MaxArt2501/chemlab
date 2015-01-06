<?php
namespace ChemLab\Utilities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Semplice classe per l'implementazione di API REST
 */
class SimpleRESTController extends Controller implements SimpleRESTControllerInterface {
	protected $repository;

	public function restAction($id, Request $request) {
		$method = $request->getMethod();

		$response = new Response('', 200, array( 'content-type' => 'application/json' ));

		switch ($method) {

			case Request::METHOD_GET:
				$entity = $this->getEntity($id);
				if ($entity)
					$retobj = $entity->toArray();
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

				if ($method === Request::METHOD_POST) {
					$entity = $this->getEntity();
				} else {
					$entity = $this->getEntity($id);
					if (!$entity) {
						$retobj = array( 'error' => 'Oggetto non trovato' );
						break;
					}
				}

				$entity->fromArray($input);
				$validator = $this->get('validator');
				$errors = $validator->validate($entity);

				if (count($errors)) {
					$retobj = array( 'error' => 'Input non corretto', 'fields' => array() );
					foreach ($errors as $error)
						$retobj['fields'][$error->getPropertyPath()] = $error->getMessage();
					break;
				}

				$manager = $this->getDoctrine()->getManager();
				$manager->persist($entity);
				$manager->flush();

				$response->setStatusCode(204);

				return $response;

			case Request::METHOD_DELETE:
				$entity = $this->getEntity($id);
				if ($entity) {

					$manager = $this->getDoctrine()->getManager();
					$manager->remove($entity);
					$manager->flush();
					$response->setStatusCode(204);

					return $response;

				} else $retobj = array( 'error' => 'Oggetto non trovato' );
				break;
		}

		$response->setContent(json_encode($retobj));

        return $response;
    }

	public function listAction($start, $end, $sort, Request $request) {
		$start = intval($start);
		$end = intval($end);

		$response = new Response('', 200, array( 'content-type' => 'application/json' ));

		if ($start > $end) {
			$response->setContent(json_encode(array( 'error' => 'Selezione non valida' )));
			return $response;
		}

		$repo = $this->getDoctrine()->getRepository($this->repository);
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
	 * @param string $id  ID dell'oggetto Item da trovare. Se null crea una nuova istanza dell'oggetto
	 * @return mixed
	 */
	protected function getEntity($id = null) {
		$doctrine = $this->getDoctrine();

		if ($id === null) {
			$clname = $doctrine->getManager()->getClassMetadata($this->repository)->getName();

			return $clname ? new $clname() : null;
		}

		return $doctrine->getRepository($this->repository)->find($id);
	}
}
?>