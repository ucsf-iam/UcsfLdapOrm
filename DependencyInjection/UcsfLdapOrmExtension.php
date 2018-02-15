<?php

namespace Ucsf\LdapOrmBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class UcsfLdapOrmExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Make the configuration available as a parameter
//        $container->setParameter('ucsf_ldap_orm', $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }


    public function load(array $configs, ContainerBuilder $container) {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $entityManagerAliasTemplate = 'ucsf_ldap_orm.%s';
        $container->setParameter('ucsf_ldap_orm_config', $config);
        $entityManagers = $config['entity_managers'];
        $connections = $config['connections'];

        foreach ($entityManagers as $entityManagerName => $entityManagerConfig) {
            $entityManagerAlias = sprintf($entityManagerAliasTemplate, $entityManagerName);
            $container->setAlias($entityManagerAlias, 'ucsf_ldap_orm_entity_manager');
            $container
                ->setDefinition($entityManagerAlias, new Definition(EntityManager::class))
                ->setArguments([
                    $connections[$entityManagerConfig['connection']],
                    empty($entityManagerConfig['repositories']) ? [] : $entityManagerConfig['repositories'],
                    empty($entityManagerConfig['commands']) ? [] : $entityManagerConfig['commands']
                ]);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }    
}
