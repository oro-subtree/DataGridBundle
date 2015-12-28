<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Entity\GridView;

class GridViewsExtension extends AbstractExtension
{
    const GRID_VIEW_ROOT_PARAM = '_grid_view';
    const DISABLED_PARAM       = '_disabled';

    const VIEWS_LIST_KEY           = 'views_list';
    const VIEWS_PARAM_KEY          = 'view';
    const MINIFIED_VIEWS_PARAM_KEY = 'v';
    const DEFAULT_VIEW_ID = '__all__';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var GridView|null */
    protected $defaultGridView;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param SecurityFacade           $securityFacade
     * @param TranslatorInterface      $translator
     * @param ManagerRegistry          $registry
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        ManagerRegistry $registry
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade  = $securityFacade;
        $this->translator      = $translator;
        $this->registry        = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return !$this->isDisabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * @return bool
     */
    protected function isDisabled()
    {
        $parameters = $this->getParameters()->get(self::GRID_VIEW_ROOT_PARAM, []);

        return !empty($parameters[self::DISABLED_PARAM]);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $currentViewId = $this->getCurrentViewId($config);
        $this->setDefaultParams($config);

        $data->offsetAddToArray('initialState', ['gridView' => self::DEFAULT_VIEW_ID]);
        $data->offsetAddToArray('state', ['gridView' => $currentViewId]);

        $allLabel = null;
        if (isset($config['options'])
                &&isset($config['options']['gridViews'])
                && isset($config['options']['gridViews']['allLabel'])
            ) {
            $allLabel = $this->translator->trans($config['options']['gridViews']['allLabel']);
        }

        /** @var AbstractViewsList $list */
        $list = $config->offsetGetOr(self::VIEWS_LIST_KEY, false);
        $gridViews = [
            'choices' => [
                [
                    'label' => $allLabel,
                    'value' => self::DEFAULT_VIEW_ID,
                ],
            ],
            'views' => [
                (new View(self::DEFAULT_VIEW_ID))->getMetadata(),
            ],
        ];
        if ($list !== false) {
            $configuredGridViews = $list->getMetadata();
            $configuredGridViews['views'] = array_merge($gridViews['views'], $configuredGridViews['views']);
            $configuredGridViews['choices'] = array_merge($gridViews['choices'], $configuredGridViews['choices']);
            $gridViews = $configuredGridViews;
        }

        if ($this->eventDispatcher->hasListeners(GridViewsLoadEvent::EVENT_NAME)) {
            $event = new GridViewsLoadEvent($config->getName(), $gridViews);
            $this->eventDispatcher->dispatch(GridViewsLoadEvent::EVENT_NAME, $event);
            $gridViews = $event->getGridViews();
        }

        $gridViews['gridName'] = $config->getName();
        $gridViews['permissions'] = $this->getPermissions();
        $data->offsetAddToArray('gridViews', $gridViews);
    }

    /**
     * Gets id for current grid view
     *
     * @param DatagridConfiguration $config
     *
     * @return int|string
     */
    protected function getCurrentViewId(DatagridConfiguration $config)
    {
        $params = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
        if (isset($params[self::VIEWS_PARAM_KEY])) {
            return (int)$params[self::VIEWS_PARAM_KEY];
        } else {
            return $this->getDefaultViewId($config->getName());
        }
    }

    /**
     * Gets id for defined as default grid view for current logged user.
     * If is not defined returns id for raw configured grid.
     *
     * @param string $gridName
     *
     * @return int|string
     */
    protected function getDefaultViewId($gridName)
    {
        $defaultGridView = $this->getDefaultView($gridName);

        return $defaultGridView ? $defaultGridView->getId() : self::DEFAULT_VIEW_ID;
    }

    /**
     * Gets defined as default grid view for current logged user.
     *
     * @param string $gridName
     *
     * @return GridView|null
     */
    protected function getDefaultView($gridName)
    {

        if ($this->defaultGridView === null &&
            $this->securityFacade->isGranted('oro_datagrid_gridview_view')
        ) {
            $repository      = $this->registry->getRepository('OroDataGridBundle:GridView');
            $defaultGridView = $repository->findDefaultGridView(
                $gridName,
                $this->securityFacade->getLoggedUser()
            );

            $this->defaultGridView = $defaultGridView;
        }

        return $this->defaultGridView;
    }

    /**
     * Sets default parameters.
     * Added filters and sorters for defined as default grid view for current logged user.
     *
     * @param DatagridConfiguration $config
     */
    protected function setDefaultParams(DatagridConfiguration $config)
    {
        $params = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

        $params[self::VIEWS_PARAM_KEY] = $this->getDefaultViewId($config->getName());

        $defaultGridView = $this->getDefaultView($config->getName());
        if ($defaultGridView) {
            $this->getParameters()->mergeKey('_filter', $defaultGridView->getFiltersData());
            $this->getParameters()->mergeKey('_sort_by', $defaultGridView->getSortersData());
        }
        $this->getParameters()->set(ParameterBag::ADDITIONAL_PARAMETERS, $params);
    }

    /**
     * @return array
     */
    private function getPermissions()
    {
        return [
            'VIEW' => $this->securityFacade->isGranted('oro_datagrid_gridview_view'),
            'CREATE' => $this->securityFacade->isGranted('oro_datagrid_gridview_create'),
            'EDIT' => $this->securityFacade->isGranted('oro_datagrid_gridview_update'),
            'DELETE' => $this->securityFacade->isGranted('oro_datagrid_gridview_delete'),
            'SHARE' => $this->securityFacade->isGranted('oro_datagrid_gridview_publish'),
            'EDIT_SHARED' => $this->securityFacade->isGranted('oro_datagrid_gridview_update_public'),
        ];
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $additional = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

            if (array_key_exists(self::MINIFIED_VIEWS_PARAM_KEY, $minifiedParameters)) {
                $additional[self::VIEWS_PARAM_KEY] = $minifiedParameters[self::MINIFIED_VIEWS_PARAM_KEY];
            }

            $parameters->set(ParameterBag::ADDITIONAL_PARAMETERS, $additional);
        }

        parent::setParameters($parameters);
    }
}
