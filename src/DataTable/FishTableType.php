<?php

namespace App\DataTable;

use App\Entity\Fish;
use App\Form\HabitatType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Umbrella\CoreBundle\Component\Action\Action;
use Umbrella\CoreBundle\Component\Action\ActionListBuilder;
use Umbrella\CoreBundle\Component\Action\Type\AddActionType;
use Umbrella\CoreBundle\Component\Action\Type\DropdownActionType;
use Umbrella\CoreBundle\Component\Action\Type\DropdownItemActionType;
use Umbrella\CoreBundle\Component\Column\Type\BooleanColumnType;
use Umbrella\CoreBundle\Component\Column\Type\CheckBoxColumnType;
use Umbrella\CoreBundle\Component\Column\Type\LinkListColumnType;
use Umbrella\CoreBundle\Component\Column\Type\PropertyColumnType;
use Umbrella\CoreBundle\Component\DataTable\Adapter\EntityAdapter;
use Umbrella\CoreBundle\Component\DataTable\DataTableBuilder;
use Umbrella\CoreBundle\Component\DataTable\Type\DataTableType;
use Umbrella\CoreBundle\Component\Toolbar\ToolbarBuilder;
use Umbrella\CoreBundle\Component\UmbrellaLink\UmbrellaLinkList;
use Umbrella\CoreBundle\Form\SearchType;
use Umbrella\CoreBundle\Utils\HtmlUtils;
use Umbrella\CoreBundle\Utils\Utils;

/**
 * Class FishTableType
 */
class FishTableType extends DataTableType
{
    private TranslatorInterface $translator;

    /**
     * FishTableType constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildToolbar(ToolbarBuilder $builder, array $options = [])
    {
        $builder->addFilter('search', SearchType::class);
        $builder->addFilter('habitat', HabitatType::class, [
            'placeholder' => 'form.placeholder.habitat',
            'multiple' => false,
        ]);

        if (!$options['disabled']) {
            $builder->addAction('add', AddActionType::class, [
                'route' => 'app_admin_fishcrud_edit',
                'xhr' => true,
            ]);
        }

        if ($options['exportable']) {
            $builder->addAction('export', DropdownActionType::class, [
                'icon' => 'mdi mdi-file-download-outline',
                'item_builder' => function (ActionListBuilder $builder) {
                    $builder->add('export_filtered', DropdownItemActionType::class, [
                        'route' => 'app_admin_datatableexport_filtered',
                        'extra_data' => Action::DATA_DATATABLE_FILTER,
                    ]);

                    $builder->add('export_selected', DropdownItemActionType::class, [
                        'route' => 'app_admin_datatableexport_selected',
                        'extra_data' => Action::DATA_DATATABLE_SELECTION,
                    ]);
                },
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildTable(DataTableBuilder $builder, array $options = [])
    {
        if ($options['exportable']) {
            $builder->add('select', CheckBoxColumnType::class);
        }

        $builder->add('name', PropertyColumnType::class, [
            'is_safe_html' => true,
            'renderer' => function (Fish $entity) {
                return sprintf(
                    '<div class="d-flex align-items-center">' .
                    '<i class="mdi mdi-square mdi-36px" style="color: %s"></i>' .
                    '<div>%s<br><span class="text-muted">%s</span></div>' .
                    '</div>',
                    $entity->color,
                    HtmlUtils::escape($entity->name),
                    Utils::truncate(HtmlUtils::escape($entity->description), 150),
                );
            },
            'order' => 'ASC',
        ]);
        $builder->add('edible', BooleanColumnType::class);

        $builder->add('habitats', PropertyColumnType::class, [
            'is_safe_html' => true,
            'order' => false,
            'renderer' => function (Fish $fish) {
                $html = '';
                foreach ($fish->habitats as $habitat) {
                    $html .= sprintf('<span class="badge badge-primary">%s</span>', $this->translator->trans('fish.habitat.' . $habitat));
                }

                return $html;
            },
        ]);

        if (!$options['disabled']) {
            $builder->add('actions', LinkListColumnType::class, [
                'link_builder' => function (UmbrellaLinkList $linkList, Fish $entity) {
                    $linkList->addXhrEdit('app_admin_fishcrud_edit', ['id' => $entity->id]);
                    $linkList->addXhrDelete('app_admin_fishcrud_delete', ['id' => $entity->id]);
                },
            ]);
        }

        $builder->useAdapter(EntityAdapter::class, [
            'class' => Fish::class,
            'query' => function (QueryBuilder $qb, array $formData) use ($options) {
                if (isset($formData['search'])) {
                    $qb->andWhere('lower(e.search) LIKE :search');
                    $qb->setParameter('search', '%' . strtolower($formData['search']) . '%');
                }

                if (null !== $options['edible']) {
                    $qb->andWhere('e.edible = :edible');
                    $qb->setParameter('edible', $options['edible']);
                }

                if (isset($formData['habitat'])) {
                    $qb->andWhere('e.habitats LIKE :habitat');
                    $qb->setParameter('habitat', '%' . $formData['habitat'] . '%');
                }
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'edible' => null, // null : return all | true => return only edible fish | false => return only not edible fish
            'disabled' => false, // <=> not editable
            'exportable' => false
        ]);
    }
}
