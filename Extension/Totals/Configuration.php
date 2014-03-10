<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const TOTALS_PATH          = '[totals]';
    const COLUMNS_PATH         = '[totals][columns]';

    const TOTALS_LABEL_KEY     = 'label';
    const TOTALS_QUERY_KEY     = 'query';
    const TOTALS_FORMATTER     = 'formatter';

    const TOTALS_PER_PAGE_ROW  = 'per_page';
    const TOTALS_HIDE_ON_FULL_SET  = 'hide_on_full_dataset';
    const TOTALS_EXTEND         = 'extend';


    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('totals')
            ->useAttributeAsKey('rows')
            ->prototype('array')
                ->children()
                    ->scalarNode(self::TOTALS_PER_PAGE_ROW)
                        ->defaultFalse()
                    ->end()
                    ->scalarNode(self::TOTALS_HIDE_ON_FULL_SET)
                        ->defaultFalse()
                    ->end()
                    ->scalarNode(self::TOTALS_EXTEND)
                    ->end()
                    ->arrayNode('columns')
                        ->prototype('array')
                            ->children()
                                ->scalarNode(self::TOTALS_LABEL_KEY)
                                    ->end()
                                ->scalarNode(self::TOTALS_QUERY_KEY)
                                    ->end()
                                ->scalarNode(self::TOTALS_FORMATTER)
                                    ->defaultFalse()
                                    ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
