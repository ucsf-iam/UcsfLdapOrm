<?php

namespace Ucsf\LdapOrmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ucsf_ldap_orm');

        $rootNode
            ->useAttributeAsKey(true)->prototype('array')
            ->children()
            ->scalarNode('uri')->isRequired()->cannotBeEmpty()->end()
            ->booleanNode('use_tls')->defaultFalse()->end()
            ->scalarNode('bind_dn')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('password_type')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('active_directory')->end()
            ->scalarNode('domain')->cannotBeEmpty()->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
