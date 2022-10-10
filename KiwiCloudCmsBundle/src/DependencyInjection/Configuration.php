<?php

namespace AraSolutions\KiwiCloudCmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kiwi_cloud_cms');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('version')->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}