<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Company;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Validator\Constraints\UniqueAlias;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class CompanySegment extends FormEntity
{
    public const TABLE_NAME    = 'company_segments';
    public const LINKED_ENTITY = 'company';
    public const DEFAULT_ALIAS = 'cs';

    private ?int $id = null;

    private ?string $name = null;

    private ?string $publicName = null;

    private ?Category $category = null;

    private ?string $description = null;

    private ?string $alias = null;

    /**
     * @var array<array<mixed>>
     */
    private array $filters = [];

    /**
     * @var Collection<int, CompaniesSegments>
     */
    private Collection $companiesSegments;

    private ?\DateTimeInterface $lastBuiltDate = null;

    private ?float $lastBuiltTime = null;

    public function __construct()
    {
        $this->companiesSegments = new ArrayCollection();
    }

    public static function loadMetadata(ORMClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(CompanySegmentRepository::class)
            ->addLifecycleEvent('initializeLastBuiltDate', 'prePersist')
            ->addIndex(['alias'], 'company_segment_alias');

        $builder->addIdColumns();

        $builder->addField('alias', 'string');

        $builder->createField('publicName', 'string')
            ->columnName('public_name')
            ->build();

        $builder->addCategory();

        $builder->addField('filters', 'json');

        $builder->createOneToMany('companiesSegments', CompaniesSegments::class)
            ->mappedBy('companySegment')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('lastBuiltDate', 'datetime')
            ->columnName('last_built_date')
            ->nullable()
            ->build();

        $builder->createField('lastBuiltTime', 'float')
            ->columnName('last_built_time')
            ->nullable()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank(
            ['message' => 'mautic.core.name.required']
        ));

        $metadata->addConstraint(new UniqueAlias([
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique',
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('companySegment')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'publicName',
                    'alias',
                    'description',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'filters',
                ]
            )
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        if (null === $this->alias || '' === $this->alias) {
            $this->setAlias($name);
        }

        if (null === $this->publicName || '' === $this->publicName) {
            $this->setPublicName($name);
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setDescription(?string $description): self
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCategory(?Category $category): self
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getPublicName(): ?string
    {
        return $this->publicName;
    }

    public function setPublicName(?string $publicName): self
    {
        if (null === $publicName || '' === $publicName) {
            $publicName = $this->name;
        }

        $this->isChanged('publicName', $publicName);
        $this->publicName = $publicName;

        return $this;
    }

    /**
     * @param array<array<mixed>> $filters
     */
    public function setFilters(array $filters): self
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<array<mixed>>
     */
    public function getFilters(): array
    {
        // Copy from PR #12214
        $filters = $this->filters;
        foreach ($filters as &$filter) {
            \assert(is_array($filter));
            $filter['glue'] = 'and';
            break;
        }

        return $filters;
    }

    public function hasFilterTypeOf(string $type): bool
    {
        foreach ($this->getFilters() as $filter) {
            if ($filter['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    public function setAlias(?string $alias): self
    {
        if (null === $alias || '' === $alias) {
            $alias = $this->name;
        }

        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * The getter is used in tests.
     *
     * @return Collection<int, CompaniesSegments>
     */
    public function getCompaniesSegments(): Collection
    {
        return $this->companiesSegments;
    }

    public function addCompaniesSegment(CompaniesSegments $companiesSegments): void
    {
        if ($this->companiesSegments->contains($companiesSegments)) {
            return;
        }

        if ($this->companiesSegments->exists(static function (int $key, CompaniesSegments $companySegment) use ($companiesSegments): bool {
            return $companySegment->getCompanySegment() === $companiesSegments->getCompanySegment()
                && $companySegment->getCompany() === $companiesSegments->getCompany();
        })) {
            return;
        }

        $this->companiesSegments->add($companiesSegments);
    }

    public function removeCompaniesSegment(CompaniesSegments $companiesSegments): void
    {
        if (!$this->companiesSegments->contains($companiesSegments)) {
            return;
        }

        $this->companiesSegments->removeElement($companiesSegments);
    }

    public function hasCompany(Company $company): bool
    {
        return $this->companiesSegments->exists(static function (int $key, CompaniesSegments $companiesSegments) use ($company): bool {
            return $companiesSegments->getCompany() === $company;
        });
    }

    public function getLastBuiltDate(): ?\DateTimeInterface
    {
        return $this->lastBuiltDate;
    }

    public function setLastBuiltDate(?\DateTimeInterface $lastBuiltDate): void
    {
        $this->lastBuiltDate = $lastBuiltDate;
    }

    public function setLastBuiltDateToCurrentDatetime(): void
    {
        $now = (new DateTimeHelper())->getUtcDateTime();
        $this->setLastBuiltDate($now);
    }

    public function initializeLastBuiltDate(): void
    {
        if ($this->getLastBuiltDate() instanceof \DateTimeInterface) {
            return;
        }

        $this->setLastBuiltDateToCurrentDatetime();
    }

    public function getLastBuiltTime(): ?float
    {
        return $this->lastBuiltTime;
    }

    public function setLastBuiltTime(?float $lastBuiltTime): void
    {
        $this->lastBuiltTime = $lastBuiltTime;
    }

    /**
     * Clone entity with empty companies list.
     */
    public function __clone()
    {
        parent::__clone();

        $this->id                = null;
        $this->companiesSegments = new ArrayCollection();
        $this->setIsPublished(false);
        $this->setAlias('');
        $this->lastBuiltDate = null;
    }
}
