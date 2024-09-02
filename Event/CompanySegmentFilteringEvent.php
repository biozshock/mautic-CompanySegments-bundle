<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Event is executed when the company segment is filtered.
 */
class CompanySegmentFilteringEvent extends CommonEvent
{
    private bool $isFilteringDone;

    private string $subQuery;

    private string $companiesTableAlias;

    public function __construct(
        private ContactSegmentFilterCrate $details,
        private string $alias,
        private QueryBuilder $queryBuilder,
        EntityManagerInterface $entityManager
    ) {
        $this->em              = $entityManager;
        $this->isFilteringDone = false;
        $this->subQuery        = '';
        $tableAlias            = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'companies');
        \assert(is_string($tableAlias));
        $this->companiesTableAlias = $tableAlias;
    }

    /**
     * Call getDetails()->getArray() if you need an array.
     */
    public function getDetails(): ContactSegmentFilterCrate
    {
        return $this->details;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getFunc(): string
    {
        return $this->details->getArray()['operator'];
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setFilteringStatus(bool $status): void
    {
        $this->isFilteringDone = $status;
    }

    public function setSubQuery(string $query): void
    {
        $this->subQuery = $query;

        $this->setFilteringStatus(true);
    }

    public function isFilteringDone(): bool
    {
        return $this->isFilteringDone;
    }

    public function getSubQuery(): string
    {
        return $this->subQuery;
    }

    public function setDetails(ContactSegmentFilterCrate $details): void
    {
        $this->details = $details;
    }

    public function getCompaniesTableAlias(): string
    {
        return $this->companiesTableAlias;
    }
}
