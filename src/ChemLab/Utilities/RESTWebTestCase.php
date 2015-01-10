<?php
namespace ChemLab\Utilities;

use ChemLab\Utilities\AuthWebTestCase;

class RESTWebTestCase extends AuthWebTestCase {
	/**
	 * @var string
	 */
	protected $entityClass;

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	protected $repository;

	/**
	 * @var Entity[]
	 */
	protected $entities;

	protected function setEntityClass($class) {
		$this->assertTrue(class_exists($class));

		$this->entityClass = $class;
		$this->repository = $this->manager->getRepository($class);
	}

	/**
	 * Restituisce il numero di entità nel repository, eventualmente filtrate
	 * secondo le condizioni fornite.
	 * @param array $filterParams
	 * @return number
	 */
	protected function getEntityCount(array $filterParams = null) {
		$qb = $this->repository->createQueryBuilder('i');

		$filter = $this->buildFilter($qb, $filterParams);

		$qb->select($qb->expr()->count('i.id'));
		if ($filter !== null)
			$qb->where($filter);

		$result = $qb->getQuery()->getSingleResult();

		return intval($result[1]);
	}

	/**
	 * Restituisce un array di entity con un filtro applicato.
	 * Per condizioni più precise, considerare $this->repository->findBy
	 * @param array $filterParams
	 * @return Entity[]
	 */
	protected function getEntities(array $filterParams = null) {
		$qb = $this->repository->createQueryBuilder('i')->select();

		$filter = $this->buildFilter($qb, $filterParams);

		if ($filter !== null)
			$qb->where($filter);

		return $qb->getQuery()->getResult();
	}

	/**
	 * Costruisce un filtro per la condizione WHERE di una query sul repository
	 * definito.
	 * @param \Doctrine\ORM\QueryBuilder $builder
	 * @param array $filterParams
	 * @return \Doctrine\ORM\Query\Expr
	 */
	protected function buildFilter($builder, $filterParams) {
		if (empty($filterParams)) return null;

		$filter = null;
		$filters = [];
		$meta = $this->manager->getClassMetadata($this->entityClass);

		// Si scorrono i campi della query string, usando solo quelli validi (cioè
		// quelli effettivamente definiti sull'entità, anche con chiavi esterne)
		foreach ($filterParams as $key => $value) {

			// Si verifica che la chiave si un campo "regolare"
			if (array_key_exists($key, $meta->fieldMappings)) {

				$type = $meta->fieldMappings[$key]['type'];
				if (in_array($type, ['boolean', 'integer', 'smallint', 'bigint',
						'datetime', 'datetimetz', 'date', 'time', 'decimal', 'float']))
					$filters[] = $builder->expr()->eq("i.$key", $value);
				elseif (in_array($type, ['string', 'text']))
					$filters[] = $builder->expr()->like("i.$key",
							$builder->expr()->literal("$value%"));

			// Si verifica che la chiave sia un campo con chiave esterna
			} elseif (array_key_exists($key, $meta->associationMappings)
					&& is_null($meta->associationMappings[$key]['mappedBy'])
					&& is_integer($value)) {

				$filters[] = $builder->expr()->eq("i.$key", $value);

			}
		}
		// Se sono stati definiti filtri validi, si imposta la condizione where
		if (!empty($filters)) {
			// Se ci sono più filtri, li si mette in un'unica condizione and
			if (count($filters) > 1) {
				$expr = $builder->expr();
				$filter = call_user_func_array([ $expr, 'andX' ], $filters);
			} else $filter = $filters[0];
			$builder->where($filter);
		}

		return $filter;
	}
}