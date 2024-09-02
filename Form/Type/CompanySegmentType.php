<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Entity\CompanySegment;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Model\CompanySegmentModel;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Validator\Constraints\CircularDependency;
use MauticPlugin\LeuchtfeuerCompanySegmentsBundle\Validator\Constraints\SegmentDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanySegmentType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private CompanySegmentModel $companySegmentModel
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html', 'name' => 'clean', 'publicName' => 'clean', 'filter' => 'raw']));
        $builder->addEventSubscriber(new FormExitSubscriber(CompanySegmentModel::class, $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'publicName',
            TextType::class,
            [
                'label'      => 'mautic.company_segments.form.publicname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.company_segments.form.publicname.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.company_segments.help.alias',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'company_segment',
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $filterModalTransformer = new FieldFilterTransformer($this->translator, ['object' => CompanySegment::LINKED_ENTITY]);
        $builder->add(
            $builder->create(
                'filters',
                CollectionType::class,
                [
                    'entry_type'     => FilterType::class,
                    'error_bubbling' => false,
                    'mapped'         => true,
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'label'          => false,
                    'constraints'    => [
                        new CircularDependency([
                            'message' => 'mautic.core.segment.circular_dependency_exists',
                        ]),
                        new SegmentDate([
                            'message' => 'mautic.lead.segment.date_invalid',
                        ]),
                    ],
                ]
            )->addModelTransformer($filterModalTransformer)
        );

        $builder->add('buttons', FormButtonsType::class);

        if (is_string($options['action']) && '' !== $options['action']) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CompanySegment::class,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['fields'] = $this->companySegmentModel->getChoiceFields();
    }

    public function getBlockPrefix(): string
    {
        return CompanySegment::TABLE_NAME;
    }
}
