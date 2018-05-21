<?php

/*
 * Copyright (c) 2017-present trivago GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Trivago\Jade\Infrastructure\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Trivago\Jade\Domain\Resource\Constraint;
use Trivago\Jade\Domain\Resource\ExpressionFilter;
use Trivago\Jade\Domain\Resource\CompositeFilter;
use Trivago\Jade\Domain\Resource\ExpressionFilterTypes;
use Trivago\Jade\Domain\Resource\SortCollection;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceCounter;
use Trivago\Jade\Domain\ResourceManager\Repository\ResourceRepository;

class DoctrineResourceRepository implements ResourceRepository, ResourceCounter
{
    const NON_NULL_OPERATORS_MAP = [
        ExpressionFilterTypes::EQUAL_TO           => '=',
        ExpressionFilterTypes::NOT_EQUAL_TO       => '!=',
        ExpressionFilterTypes::GREATER_THAN       => '>',
        ExpressionFilterTypes::GREATER_THAN_EQUAL => '>=',
        ExpressionFilterTypes::LESS_THAN          => '<',
        ExpressionFilterTypes::LESS_THAN_EQUAL    => '<=',
        ExpressionFilterTypes::CONTAINS           => 'LIKE',
        ExpressionFilterTypes::NOT_CONTAINS       => 'NOT LIKE',
        ExpressionFilterTypes::IN                 => 'IN',
        ExpressionFilterTypes::NOT_IN             => 'NOT IN',
    ];

    const NULL_OPERATORS_MAP = [
        ExpressionFilterTypes::EQUAL_TO     => 'IS NULL',
        ExpressionFilterTypes::NOT_EQUAL_TO => 'IS NOT NULL',
    ];

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var EntityRepository
     */
    private $doctrineRepository;

    /**
     * @param Registry         $doctrine
     * @param EntityRepository $doctrineRepository
     */
    public function __construct(Registry $doctrine, EntityRepository $doctrineRepository)
    {
        $this->doctrine = $doctrine;
        $this->doctrineRepository = $doctrineRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NonUniqueResultException
     */
    public function fetchOneResource($id, array $relationships)
    {
        $queryBuilder = $this->doctrineRepository
            ->createQueryBuilder('r')
            ->where('r.id = :id')
            ->setParameter('id', $id)
        ;

        self::addRelationships($queryBuilder, 'r', $relationships);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchResourcesByIds(array $ids, array $relationships)
    {
        $queryBuilder = $this->doctrineRepository
            ->createQueryBuilder('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        self::addRelationships($queryBuilder, 'r', $relationships);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchResourcesWithConstraint(array $relationships, Constraint $constraint, SortCollection $sortCollection)
    {
        $prefix = 'r';
        $queryBuilder = $this->createQuery($prefix, $relationships, $constraint, $sortCollection);
        $queryBuilder->select('r.id');
        $queryBuilder->groupBy('r.id');

        $idsResult = $queryBuilder->getQuery()->getScalarResult();
        $ids = array_map('array_pop', $idsResult);

        $idsConstraint = new Constraint();
        $idsConstraint->setPerPage(PHP_INT_MAX);
        $idBasedQueryBuilder = $this->createQuery($prefix, $relationships, $idsConstraint, $sortCollection);
        $idBasedQueryBuilder->where($prefix.'.id IN (:ids)')->setParameters(['ids' => $ids]);

        return $idBasedQueryBuilder->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteResource($resource)
    {
        $this->doctrine->getManager()->remove($resource);
        $this->doctrine->getManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function saveResource($resource)
    {
        $this->doctrine->getManager()->persist($resource);
        $this->doctrine->getManager()->flush();
    }

    /**
     * {@inheritdoc}
     * @throws NonUniqueResultException
     */
    public function countResourcesWithFilters(array $relationships, CompositeFilter $filterCollection)
    {
        $prefix = 'r';
        $queryBuilder = $this->doctrineRepository
            ->createQueryBuilder($prefix);
        self::addRelationships($queryBuilder, $prefix, $relationships);
        $queryBuilder->select($queryBuilder->expr()->countDistinct($prefix.'.id'));
        $this->addFilters($queryBuilder, $filterCollection);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string[]     $relationships
     */
    public static function addRelationships(QueryBuilder $queryBuilder, $alias, array $relationships)
    {
        $addedRelationships = [];
        foreach ($relationships as $relationship) {
            $relationshipChain = [];
            $fullAlias = $alias;
            foreach (explode('.', $relationship) as $subRelationship) {
                $relationshipChain[] = $subRelationship;
                $newFullAlias = self::buildAlias($alias, $relationshipChain);
                if (!in_array($newFullAlias, $addedRelationships, true)) {
                    $queryBuilder->leftJoin($fullAlias.'.'.$subRelationship, $newFullAlias);
                    $queryBuilder->addSelect($newFullAlias);
                    $addedRelationships[] = $newFullAlias;
                }
                $fullAlias = $newFullAlias;
            }
        }
    }

    /**
     * @param string   $rootAlias
     * @param string[] $relationshipChain
     *
     * @return string
     */
    public static function buildAlias($rootAlias, array $relationshipChain)
    {
        $separator = count($relationshipChain) ? '_' : '';

        return $rootAlias.$separator.implode('__', $relationshipChain);
    }

    /**
     * @param string         $prefix
     * @param array          $relationships
     * @param Constraint     $constraint
     * @param SortCollection $sortCollection
     *
     * @return QueryBuilder
     */
    private function createQuery($prefix, array $relationships, Constraint $constraint, SortCollection $sortCollection)
    {
        $queryBuilder = $this->doctrineRepository
            ->createQueryBuilder($prefix);
        self::addRelationships($queryBuilder, $prefix, $relationships);

        $queryBuilder->setMaxResults($constraint->getPerPage());
        $queryBuilder->setFirstResult(($constraint->getPageNumber()-1)*$constraint->getPerPage());

        $this->addFilters($queryBuilder, $constraint->getFilterCollection());

        foreach ($sortCollection->getSorts() as $sort) {
            $alias = self::buildAlias($prefix, $sort->getPath()->getRelationshipChain());
            $queryBuilder->addOrderBy($this->joinAliasAndColumnName($alias, $sort->getPath()->getColumnName()), $sort->getDirection());
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder    $queryBuilder
     * @param CompositeFilter $compositeFilter
     */
    private function addFilters(QueryBuilder $queryBuilder, CompositeFilter $compositeFilter)
    {
        if (count($compositeFilter->getFilters())) {
            $queryBuilder->where($this->createFilterExpressions($queryBuilder, $compositeFilter, 'value_'));
        }
    }

    /**
     * @param QueryBuilder    $queryBuilder
     * @param CompositeFilter $compositeFilter
     * @param string          $baseKey
     *
     * @return string
     */
    private function createFilterExpressions(QueryBuilder $queryBuilder, CompositeFilter $compositeFilter, $baseKey)
    {
        $expressions = [];
        foreach ($compositeFilter->getFilters() as $key => $filter) {
            if ($filter instanceof CompositeFilter) {
                $expressions[] = $this->createFilterExpressions($queryBuilder, $filter, $baseKey.$key.'_');
                continue;
            }
            /** @var ExpressionFilter $filter */
            $alias = self::buildAlias('r', $filter->getPath()->getRelationshipChain());
            $column = $this->joinAliasAndColumnName($alias, $filter->getPath()->getColumnName());
            $value = $filter->getValue();
            if (in_array($filter->getType(), [ExpressionFilterTypes::CONTAINS, ExpressionFilterTypes::NOT_CONTAINS], true)) {
                $value = "%$value%";
            }

            $operator = self::NON_NULL_OPERATORS_MAP[$filter->getType()];

            $isNullComparison = ($filter->isType(ExpressionFilterTypes::EQUAL_TO)
                || $filter->isType(ExpressionFilterTypes::NOT_EQUAL_TO))
                && null === $filter->getValue();
            $isCollectionFilter = $filter->isType(ExpressionFilterTypes::IN)
                || $filter->isType(ExpressionFilterTypes::NOT_IN);

            if ($isCollectionFilter) {
                $expressions[] = "$column $operator (:$baseKey$key)";
            } elseif ($isNullComparison) {
                $operator = self::NULL_OPERATORS_MAP[$filter->getType()];
                $expressions[] = "$column $operator";
            } else {
                $expressions[] = "$column $operator :$baseKey$key";
            }

            if (!$isNullComparison) {
                $queryBuilder->setParameter($baseKey.$key, $value);
            }
        }

        return '('.implode(' '.strtoupper($compositeFilter->getType()).' ', $expressions).')';
    }

    /**
     * @param string $alias
     * @param string $columnName
     *
     * @return string
     */
    private function joinAliasAndColumnName($alias, $columnName)
    {
        $this->validateIdentifier($alias);
        $this->validateIdentifier($columnName);

        return $alias.'.'.$columnName;
    }

    /**
     * @param string $string
     */
    private function validateIdentifier($string)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $string)) {
            //It smells fishy!
            throw new \InvalidArgumentException(
                sprintf('The string "%s" is not valid to be used as an identifier in the query', $string)
            );
        }
    }
}
