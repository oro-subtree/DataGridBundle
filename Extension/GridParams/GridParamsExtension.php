<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridParams;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class GridParamsExtension extends AbstractExtension
{
    const MINIFIED_GRID_PARAM_KEY = 'g';
    const GRID_PARAM_KEY = 'grid';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $params = $this->getParameters()->all();
        $gridParams = array_filter(
            $params,
            function ($param) {
                return !is_array($param) && !is_null($param);
            }
        );

        $data->offsetAddToArray('gridParams', $gridParams);
    }
}
