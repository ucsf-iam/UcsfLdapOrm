<?php
namespace Ucsf\LdapOrmBundle\Doctrine\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Setup;
use Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP\LDAPConnection;
use Ucsf\LdapOrmBundle\Entity\Ldap\LdapEntity;
use Ucsf\LdapOrmBundle\Exception\LdapOrmException;
use Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP\Driver;
use Ucsf\LdapOrmBundle\LdapStdClass;

/**
 * Class EntityManager
 *
 * A custom Doctrine-line entity manager for LDAP services
 *
 * @package Ucsf\LdapOrmBundle\Doctrine
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class EntityManager {

    protected $connection;
    protected $doctrineEntityManager; // A true Doctrine entity manager for gathering entity metadata


    /**
     * EntityManager constructor.
     * @param $connection
     */
    public function __construct($connection)
    {
        $this->connection = new LDAPConnection($connection['uri'], $connection['bind_dn'], $connection['password']);
        $this->doctrineEntityManager = \Doctrine\ORM\EntityManager::create(
            array('driverClass' => Driver::class),
            Setup::createAnnotationMetadataConfiguration(array(__DIR__.'/src'), false, null, null, FALSE)
        );
    }

    public function getRepository($class)
    {
        return new Repository(
            $this,
            $this->doctrineEntityManager->getClassMetadata($class),
            'some config'
        );
    }


    /**
     * @param $class
     * @param $filter
     * @return mixed
     * @throws \Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP\LDAPException
     */
    public function find($class, $filter) {
        $metadata = $this->doctrineEntityManager->getClassMetadata($class);
        $baseDn = $metadata->getTableName();
        $stmt = $this->connection->query($baseDn, $filter);
        $objects = $stmt->fetchAll();
        $entities = $this->hydrateEntities($class, $objects);
        return $entities;
    }


    /**
     * @return LDAPConnection
     */
    public function getConnection() {
        return $this->connection;
    }



    /**
     * Basic persistance for the given entity via LDAP. The entity is transfered as
     * the body of the LDAP call.
     * @param $entity
     * @param null $method
     * @param array $variables
     * @return mixed
     * @throws \Exception
     */
    public function persist(LdapEntity $entity) {
        $responseObjects = $this->connection->persist($entity);
        return $this->hydrateEntities($entity, $responseObjects);
    }


    protected function hydrateEntry(array $entity) {
        if (!$entity) {
            return null;
        }
        // Record if the input is an array or a single db objects. If it is not an objet
        // wrap it in an array for operational consistency
        $isArray = is_array($entity);
        if (!$isArray) {
            $entity = array($entity);
        }

        $entityName = get_class($entity[0]);
        $restObjects = array();
        $metadata = $this->doctrineEntityManager->getClassMetadata($entityName);
        foreach($entity as $entity) {
            $restObject = new LdapStdClass();

            // Copy scalar database columns into analogous object columns
            foreach ($metadata->getColumnNames() as $columnName) {
                $fieldName = $metadata->getFieldName($columnName);
                $getter = 'get'.$fieldName;
                $restObject->$columnName = $entity->$getter();
            }

            // Recurse to satisfy non-scalar object columns (i.e. joins/associatiations)
            foreach ($metadata->getAssociationMappings() as $fieldName => $associationMapping) {
                $getter = 'get'.$fieldName;
                $associationEntities = $entity->$getter();
                if ($associationEntities) {
                    $associatedObjects = $this->hydrateObjects($associationEntities);
                    if (isset($associationMapping['joinTable']) || isset($associationMapping['joinColumns'])) {

                    if (!empty($associationMapping['joinColumns'])) foreach ($associationMapping['joinColumns'] as $joinColumn) {
                        $restObject->$fieldName = $associatedObjects;
                    }
                    if (!empty($associationMapping['joinTable'])) {
                        $restObject->$fieldName = $associatedObjects;
                        }
                    }
                }
            }
            $restObjects[] = $restObject;
        }

        // If this started with an array, return result in an array,
        // otherwise return as a single entity
        return ($isArray) ? $restObjects : array_shift($restObjects);
    }

    /**
     * Take the given objects retrieved from the LDAP call and use them to hyrdate entities of the given classname
     * @param $entityName The fully qualified name of the entity class to be hydrated
     * @param $restObjects The stdClass objects returned from the LDAP client
     * @return mixed An array of entities if the input was an array of objects, otherwise a single entity.
     */
    protected function hydrateEntities($entityName, $restObjects) {
        if (!$restObjects) {
            return null;
        }
        // Record if the input is an array or a single db objects. If it is not an objet
        // wrap it in an array for operational consistency
        $isArray = is_array($restObjects);
        if (!$isArray) {
            $restObjects = array($restObjects);
        }

        $entities = array();
        $metadata = $this->doctrineEntityManager->getClassMetadata($entityName);
        foreach($restObjects as $object) {
            $entity = new $entityName();

            // Copy scalar database columns into analogous entity fields
            foreach ($metadata->getFieldNames() as $fieldName) {
                $columnName = $metadata->getColumnName($fieldName);
                $setter = 'set'.$fieldName;
                $entity->$setter($object->$columnName);
            }

            // Recurse to satisfy non-scalar entity fields (i.e. joins/associatiations)
            foreach ($metadata->getAssociationMappings() as $columnName => $associationMapping) {
                $associationEntityName = $associationMapping['targetEntity'];
                $associatedEntities = $this->hydrateEntities($associationEntityName, $object->$columnName);
                $setter = 'set'.$associationMapping['fieldName'];
                $entity->$setter($associatedEntities);
            }

            $entities[] = $entity;
        }

        // If this started with an array, return result in an array,
        // otherwise return as a single entity
        return ($isArray) ? $entities : array_shift($entities);
    }

}