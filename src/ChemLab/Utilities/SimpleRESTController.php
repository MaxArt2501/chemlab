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

				$parsed = $this->parseInput($input);
				if (is_null($parsed)) {
					$retobj = array( 'error' => 'Azione non consentita' );
					break;
				}

				$entity->fromArray($parsed);
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
				// Si limitano i risultati
				->setFirstResult($start)
				->setMaxResults($end - $start + 1);

		// Impostazione filtri
		if ($request->query->count()) {
			$filters = [];
			$meta = $this->getDoctrine()->getManager()->getClassMetadata($this->repository);

			// Si scorrono i campi della query string, usando solo quelli validi (cioè quelli
			// effettivamente definiti sull'entità, anche con chiavi esterne)
			foreach ($request->query->all() as $key => $value) {

				// Si verifica che la chiave si un campo "regolare"
				if (array_key_exists($key, $meta->fieldMappings)) {

					$type = $meta->fieldMappings[$key]['type'];
					if (in_array($type, ['boolean', 'integer', 'smallint', 'bigint', 'datetime', 'datetimetz', 'date', 'time', 'decimal', 'float']))
						$filters[] = $qb->expr()->eq("i.$key", $value);
					elseif (in_array($type, ['string', 'text']))
						$filters[] = $qb->expr()->like("i.$key", $qb->expr()->literal("$value%"));

				// Si verifica che la chiave sia un campo con chiave esterna
				} elseif (array_key_exists($key, $meta->associationMappings)
						&& is_null($meta->associationMappings[$key]['mappedBy'])
						&& is_integer($value)) {

					$filters[] = $qb->expr()->eq("i.$key", $value);

				}
			}
			// Se sono stati definiti filtri validi, si imposta la condizione where
			if (!empty($filters)) {
				// Se ci sono più filtri, li si mette in un'unica condizione and
				if (count($filters) > 1) {
					$expr = $qb->expr();
					$filter = call_user_func_array([ $expr, 'andX' ], $filters);
				} else $filter = $filters[0];
				$qb->where($filter);
			}
		}

		// Si imposta l'eventuale ordinamento
		if (!empty($sort))
			$qb->orderBy('i.'.substr($sort, 1), $sort[0] === '+' ? 'ASC' : 'DESC');

		$query = $qb->getQuery();
		$entities = $query->getResult();

		$list = array();
		foreach ($entities as $entity)
			$list[] = $entity->toArray();

		$qb = $repo->createQueryBuilder('i')
				->select($qb->expr()->count('i.id'));
		if (isset($filter))
			$qb->where($filter);
		$total = $qb->getQuery()->getSingleResult();

		$response->setContent(json_encode(array(
			'list' => $list,
			'total' => intval($total[1])
		)));

        return $response;
	}

	/**
	 * @param string $id  ID dell'entità da trovare. Se null crea una nuova istanza dell'oggetto
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

	/**
	 * Funzione per l'elaborazione dei dati di input. Da fare override in caso di entità
	 * con relazioni esterne.
	 * @param array $input
	 * @return array
	 */
	protected function parseInput(array $input) { return $input; }
}
?>