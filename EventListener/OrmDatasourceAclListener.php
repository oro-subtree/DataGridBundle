<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\ResultBefore;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class OrmDatasourceAclListener
{
    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param ResultBefore $event
     */
    public function onResultBefore(ResultBefore $event)
    {
        $source = $event->getDatagrid()->getDatasource();
        if ($source instanceof OrmDatasource) {
            $this->aclHelper->apply($event->getQuery());
        }
    }
}
