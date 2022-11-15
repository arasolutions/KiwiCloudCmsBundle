<?php
namespace AraSolutions\KiwiCloudCmsBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KiwiCloudCmsExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        
        try {
            $loader->load('kiwi_cloud_cms.yaml');
            $loader->load('services.yaml');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('acme.social.twitter_client');
        $definition->replaceArgument(0, $config['twitter']['client_id']);
        $definition->replaceArgument(1, $config['twitter']['client_secret']);

//        foreach ($config as $key => $value) {
//            $container->setParameter('kiwi_cloud_cms.' . $key, $value);
//        }
    }
}
