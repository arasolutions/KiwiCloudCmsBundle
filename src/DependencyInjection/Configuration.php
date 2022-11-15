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
            ->arrayNode('twitter')
            ->children()
            ->integerNode('client_id')->end()
            ->scalarNode('client_secret')->end()
            ->end()
            ->end() // twitter
            ->end()
        ;

//        $treeBuilder->getRootNode()
//            ->children()
//            ->arrayNode('account')
//            ->children()
//            ->scalarNode('api_key')->isRequired()->end()
//            ->end()
//            ->end() // account
//            ->arrayNode('cache')
//            ->children()
//            ->booleanNode('feeds')->defaultValue(true)->end()
//            ->scalarNode('folder')->defaultValue('%kernel.project_dir%/var/cache/kiwi_cloud_cms')->end()
//            ->integerNode('time')->defaultValue(1200)->end()
//            ->end()
//            ->end() // cache
//            ->arrayNode('log')
//            ->children()
//            ->scalarNode('file')->defaultValue('%kernel.project_dir%/var/log/kiwi_cloud_cms.log')->end()
//            ->end()
//            ->end() // log
//            ->end()
//        ;

//        $treeBuilder->getRootNode()
//            ->children()
//                ->arrayNode('kiwi_cloud_cms')
//                    ->children()
//                        ->arrayNode('account')
//                            ->children()
//                                ->scalarNode('api_key')->end()
//                            ->end()
//                        ->end() // account
//                        ->arrayNode('cache')
//                            ->children()
//                                ->scalarNode('feeds')->end()
//                                ->scalarNode('folder')->end()
//                                ->integerNode('time')->end()
//                            ->end()
//                        ->end() // cache
//                        ->arrayNode('log')
//                            ->children()
//                                ->scalarNode('file')->end()
//                            ->end()
//                        ->end() // log
//                    ->end()
//                ->end() // kiwi_cloud_cms
//            ->end();

        return $treeBuilder;
    }
}
