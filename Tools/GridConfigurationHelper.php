<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

class GridConfigurationHelper
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var AbstractSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(
        EntityClassResolver $entityClassResolver, AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    public function getEntity(DatagridConfiguration $config)
    {
        if ($config->getDatasourceType() === SearchDatasource::TYPE) {
            $from = $config->offsetGetByPath('[source][query][from]');
            if (!$from) {
                return null;
            }

            $entityClassName = $this->mappingProvider->getEntityClass($from[0]);

            return $entityClassName;
        }

        if ($config->getDatasourceType() === OrmDatasource::TYPE) {
            $entityClassName = $config->offsetGetOr('extended_entity_name');
            if ($entityClassName) {
                return $entityClassName;
            }

            $from = $config->offsetGetByPath('[source][query][from]');
            if (!$from) {
                return null;
            }

            return $this->entityClassResolver->getEntityClass($from[0]['table']);
        }
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null
     */
    public function getEntityRootAlias(DatagridConfiguration $config)
    {
        $from = $config->offsetGetByPath('[source][query][from]');
        if ($from) {
            return $from[0]['alias'];
        }

        return null;
    }
}
