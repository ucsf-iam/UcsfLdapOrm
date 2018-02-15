<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 3/8/16
 * Time: 3:31 PM
 */

namespace Ucsf\RestOrmBundle\Doctrine\ORM;


class Repository
{
    private $entityMetadata;
    private $entityManager;
    private $config;

    public function __construct(EntityManager $entityManager, $entityMetadata, $config)
    {
        $this->entityMetadata = $entityMetadata;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function __call($name, $arguments)
    {
        if ($name == 'findAll') {
            if (empty($this->config['all'])) {
                throw new \BadMethodCallException($name.' does not exist');
            }
            $all = $this->config['all'];
            $entities = $this->entityManager->fetch($all['method'], $all['path'], array(), $this->entityMetadata->name);
            return $entities;
        }

        if (strpos($name, 'findBy') === 0) {
            if (count($arguments) < 1) {
                throw new \BadMethodCallException($name. 'must have at least one argument.');
            }

            // Make sure the field name to search by is the name of a field in the entity's metadata
            $findByIndex = lcfirst(substr($name, 4));
            $findByName = lcfirst(substr($findByIndex, 2));
            if (empty($this->config['find'])) {
                throw new \BadMethodCallException($name. ' does not exist.');
            }

            $findByConfig = $this->config['find'][$findByIndex];
            $entities = $this->entityManager->fetch(
                $findByConfig['method'],
                $findByConfig['path'],
                array($findByName => $arguments[0]),
                $this->entityMetadata->name
            );
            return $entities;
        }

        throw new \BadMethodCallException($name.' does not exist.');

    }
}