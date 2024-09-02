<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanySegment;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event is dispatched when the company is newly added to company segment.
 */
class CompanySegmentAddEvent extends Event
{
    public function __construct(private Company $company, private CompanySegment $companySegment)
    {
    }

    public function getCompanySegment(): CompanySegment
    {
        return $this->companySegment;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
