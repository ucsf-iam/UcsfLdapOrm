<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/

namespace Ucsf\LdapOrmBundle\Ldap;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Console\Output\ConsoleOutput;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Dn;
use Ucsf\LdapOrmBundle\Annotation\Ldap\DnLinkArray;
use Ucsf\LdapOrmBundle\Annotation\Ldap\DnPregMatch;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ParentDn;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Repository as RepositoryAttribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\SearchDn;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Operational;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Sequence;
use Ucsf\LdapOrmBundle\Annotation\Ldap\UniqueIdentifier;
use Ucsf\LdapOrmBundle\Components\GenericIterator;
use Ucsf\LdapOrmBundle\Entity\DateTimeDecorator;
use Ucsf\LdapOrmBundle\Entity\Ldap\LdapEntity;
use Ucsf\LdapOrmBundle\Ldap\Filter\LdapFilter;
use Ucsf\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Ucsf\LdapOrmBundle\Repository\Repository;
use Symfony\Bridge\Monolog\Logger;

/**
 * Entity Manager for LDAP
 *
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @author Jason Gabler <jasongabler@gmail.com>
 */
class LdapEntityManager
{
    const DEFAULT_MAX_RESULT_COUNT      = 100;
    const OPERAND_ADD = 'add';
    const OPERAND_MOD = 'mod';
    const OPERAND_DEL = 'del';

    private $uri        	= "";
    private $bindDN     	= "";
    private $password   	= "";
    private $passwordType 	= "";
    private $useTLS     	= FALSE;
    private $isActiveDirectory = FALSE;

    private $ldapResource;
    private $pageCookie 	= "";
    private $pageMore    	= FALSE;
    private $reader;

    private $iterator = Null;

    /**
     * LdapEntityManager constructor.
     * @param Logger $logger
     * @param \Twig_Environment $twig
     * @param Reader $reader
     * @param $config
     */
    public function __construct(Logger $logger, Reader $reader, $config)
    {
        $this->logger     	        = $logger;
        $this->twig                 = new \Twig_Environment(new \Twig_Loader_Array());
        $this->uri        	        = $config['uri'];
        $this->bindDN     	        = $config['bind_dn'];
        $this->password   	        = $config['password'];
        $this->passwordType         = $config['password_type'];
        $this->useTLS     	        = $config['use_tls'];
        $this->isActiveDirectory    = !empty($config['active_directory']);
        $this->reader     	        = $reader;
    }

    /**
     * Connect to LDAP service
     *
     * @return LDAP resource
     */
    private function connect()
    {
        // Don't permit multiple connect() calls to run
        if ($this->ldapResource) {
            return;
        }

        $this->ldapResource = ldap_connect($this->uri);
        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);

        // Switch to TLS, if configured
        if ($this->useTLS) {
            $tlsStatus = ldap_start_tls($this->ldapResource);
            if (!$tlsStatus) {
                throw new \Exception('Unable to enable TLS for LDAP connection.');
            }
            $this->logger->debug('TLS enabled for LDAP connection.');
        }

        for ($try=1; $try<=3; $try++) {
            $msg = '';
            try {
                $bindResult = ldap_bind($this->ldapResource, $this->bindDN, $this->password);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
            }
            if(!empty($bindResult)) {
                break;
            } else {
                $this->logger->warning('On try #'.$try.' cannot bind to LDAP server: ' . $this->uri . ' as ' . $this->bindDN. ' ' . $msg);
                sleep(1);
            }
        }

        if (empty($bindResult)) {
            throw new \Exception('Cannot bind to LDAP server: ' . $this->uri . ' as ' . $this->bindDN);
        }


        $this->logger->debug('Connected to LDAP server: ' . $this->uri . ' as ' . $this->bindDN . ' .');

        return $bindResult;
    }

    /**
     * Find if an entity exists in LDAP without doing an LDAP search that generates
     * warnings regarding an non-existant DN if turns out that the entity does not exist.
     * @param $entity The entity to check for existance. Entity must have all MAY attributes.
     * @return bool Returns true if the given entity exists in LDAP
     * @throws MissingEntityManagerException
     */
    public function entityExists($entity, $checkOnly = true) {
        $this->checkMust($entity);
        $entityClass = get_class($entity);
        $meta = $this->getClassMetadata($entityClass);

        $searchDn = $meta->getSearchDn();
        if (!$searchDn && $this->isActiveDirectory) {
            $searchDn = LdapEntity::getBaseDnFromDn($entity->getDn());
        }
        $uniqueIdentifier = $this->getUniqueIdentifier($entity);

        $entities = $this->retrieve($entityClass, [
            'searchDn' => $searchDn,
            'filter' => [ $uniqueIdentifier['attribute'] => $uniqueIdentifier['value'] ]
        ]);

        if ($checkOnly) {
            return (count($entities) > 0);
        } else {
            return $entities;
        }
    }

    public function getUniqueIdentifier(LdapEntity $entity, $throwExceptions = TRUE) {
        $entityClass = get_class($entity);
        $meta = $this->getClassMetadata($entityClass);
        $uniqueIdentifierAttr = $meta->getUniqueIdentifier();

        if ($uniqueIdentifierAttr) {
            $uniqueIdentifierGetter = 'get' . ucfirst($uniqueIdentifierAttr);
            $uniqueIdentifierValue = $entity->$uniqueIdentifierGetter();
        } else {
            if ($throwExceptions) {
                throw new \Exception($entityClass.' does not use the @UniqueIdentifier annotation.');
            }
        }

        if (empty($uniqueIdentifierValue) && $throwExceptions) {
            throw new \Exception($entityClass.' uses the @UniqueIdentifier annotation, but not value is provided.');
        }

        return [
            'attribute' => $uniqueIdentifierAttr,
            'value' => $uniqueIdentifierValue
        ];
    }

    /**
     * Return the class metadata instance
     *
     * @param string $entityName
     *
     * @return ClassMetaDataCollection
     */
    public function getClassMetadata($entityName)
    {
        $r = new \ReflectionClass($entityName);
        $instanceMetadataCollection = new ClassMetaDataCollection();
        $instanceMetadataCollection->name = $entityName;
        $classAnnotations = $this->reader->getClassAnnotations($r);

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof RepositoryAttribute) {
                $instanceMetadataCollection->setRepository($classAnnotation->getValue());
            }
            if ($classAnnotation instanceof ObjectClass) {
                $instanceMetadataCollection->setObjectClass($classAnnotation->getValue());
            }
            if ($classAnnotation instanceof SearchDn) {
                $instanceMetadataCollection->setSearchDn($classAnnotation->getValue());
            }
            if ($classAnnotation instanceof Dn) {
                $instanceMetadataCollection->setDn($classAnnotation->getValue());
            }
            if ($classAnnotation instanceof UniqueIdentifier) {
                $instanceMetadataCollection->setUniqueIdentifier($classAnnotation->getValue());
            }
        }

        foreach ($r->getProperties() as $publicAttr) {
            $annotations = $this->reader->getPropertyAnnotations($publicAttr);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Attribute) {
                    $varname=$publicAttr->getName();
                    $attribute=$annotation->getName();
                    $instanceMetadataCollection->addMeta($varname, $attribute);
                }
                if ($annotation instanceof DnLinkArray) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addArrayOfLink($varname, $annotation->getValue());
                }
                if ($annotation instanceof Sequence) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addSequence($varname, $annotation->getValue());
                }
                if ($annotation instanceof DnPregMatch) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addRegex($varname, $annotation->getValue());
                }
                if ($annotation instanceof ParentDn) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addParentLink($varname, $annotation->getValue());
                }
                if ($annotation instanceof ArrayField) {
                    $instanceMetadataCollection->addArrayField($varname);
                }
                if ($annotation instanceof Must) {
                    $instanceMetadataCollection->addMust($varname);
                }
                if ($annotation instanceof Operational) {
                    $instanceMetadataCollection->addOperational($varname);
                }
            }
        }

        return $instanceMetadataCollection;
    }

    /**
     * Convert an entity to array using annotation reader
     *
     * @param LdapEntity $entity
     *
     * @return array
     */
    public function entityToEntry(LdapEntity $entity)
    {
        $entityClass = get_class($entity);
        $entry=array();

        $r = new \ReflectionClass($entityClass);
        $metadata = $this->getClassMetadata($entity);
        $annotations = $this->reader->getClassAnnotations($r);

        $entry['objectClass'] = array('top');

        foreach ($annotations as $annotation) {
            if ($annotation instanceof ObjectClass && ($value = $annotation->getValue()) !== '' ) {
                array_push($entry['objectClass'], $value);
            }
        }

        foreach($metadata->getMetadatas() as $attribute) {
            $getter = 'get' . ucfirst($metadata->getKey($attribute));
            $setter = 'set' . ucfirst($metadata->getKey($attribute));

            $value  = $entity->$getter();
            if($value == null) {
                if($metadata->isSequence($metadata->getKey($attribute))) {


                    $sequence = $this->twigRender(
                        $metadata->getSequence($metadata->getKey($attribute)),
                        [ 'entity' => $entity ]
                    );

                    $value = (int) $this->generateSequenceValue($sequence);
                    $entity->$setter($value);
                }
            }
            // Specificity of ldap (incopatibility with ldap boolean)
            if(is_bool($value)) {
                if($value) {
                    $value = "TRUE";
                } else {
                    $value = "FALSE";
                }
            }

            if(is_object($value)) {
                if ($value instanceof \DateTime) {
                    $entry[$attribute] = Util::datetimeToAdDate($value);
                }
                elseif ($value instanceof DateTimeDecorator) {
                    $entry[$attribute] = (string)$value;
                }
                else {
                    $entry[$attribute] = $this->buildEntityDn($value);
                }
            } elseif(is_array($value) && !empty($value) && isset($value[0]) && is_object($value[0])) {
                $valueArray = array();
                foreach($value as $val) {
                    $valueArray[] = $this->buildEntityDn($val);
                }
                $entry[$attribute] = $valueArray;
            } elseif(strtolower($attribute) == "userpassword") {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $needle = '{CLEAR}';
                        if (strpos($val, $needle) === 0) {
                            $entry[$attribute] =  substr($val, strlen($needle));
                        }
                    }
                } else {
                    $entry[$attribute] = $value;
                }
            }  else {
                $entry[$attribute] = $value;
            }
        }

        return $entry;
    }


    /**
     * Build a DN for an entity with the use of dn annotation
     *
     * @param unknown_type $instance
     *
     * @return string
     */
    public function buildEntityDn($instance)
    {
        $instanceClassName = get_class($instance);
        $arrayInstance=array();

        $r = new \ReflectionClass($instanceClassName);
        $instanceMetadataCollection = new ClassMetaDataCollection();
        $classAnnotations = $this->reader->getClassAnnotations($r);

        $dnModel = '';
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof Dn) {
                $dnModel = $classAnnotation->getValue();
                break;
            }
        }

        $entityDn =  $this->twigRender($dnModel, ['entity' => $instance]);

        return $entityDn;
    }


    /**
     * Persist an instance in Ldap
     * @param unknown_type $entity
     */
    public function persist($entity, $checkMust = true, $originalEntity = null)
    {
        if ($checkMust) {
            $this->checkMust($entity);
        }

        $dn = $entity->getDn();
        if($dn == NULL) {
            $dn = $this->buildEntityDn($entity);
            $entity->setDn($dn);
        }

        // test if entity already exists
        if (!$originalEntity) {
            $result = $this->entityExists($entity, false);
            $originalEntity = reset($result);
            if($originalEntity == false) $originalEntity = null;
        }
        if(count($originalEntity) > 0)
        {
            return $this->ldapUpdate($dn, $entity, $originalEntity);
        }
        return $this->ldapPersist($dn, $entity);
    }

    /**
     * Delete an instance in Ldap
     * @param unknown_type $instance
     */
    public function delete($instance)
    {
        $dn = $this->buildEntityDn($instance);
        $this->logger->debug('Delete in LDAP: ' . $dn );
        $this->deleteByDn($dn, true);
        return;
    }

    /**
     * Delete an entry in ldap by Dn
     * @param string $dn
     */
    public function deleteByDn($dn, $recursive=false)
    {
        // Connect if needed
        $this->connect();

        $this->logger->debug('Delete (recursive=' . $recursive . ') in LDAP: ' . $dn );

        if($recursive == false) {
            return(ldap_delete($this->ldapResource, $dn));
        } else {
            //searching for sub entries
            $sr=ldap_list($this->ldapResource, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($this->ldapResource, $sr);

            for($i = 0; $i < $info['count']; $i++) {
                //deleting recursively sub entries
                $result=$this->deleteByDn($info[$i]['dn'], true);
                if(!$result) {
                    //return result code, if delete fails
                    return($result);
                }
            }
            return(ldap_delete($this->ldapResource, $dn));
        }
    }

    /**
     * Send entity to database
     */
    public function flush()
    {
        return;
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     *
     * @return EntityRepository The repository class.
     */
    public function getRepository($entityName)
    {
        $metadata = $this->getClassMetadata($entityName);
        if($metadata->getRepository()) {
            $repository = $metadata->getRepository();
            return new $repository($this, $metadata);
        }
        return new Repository($this, $metadata);
    }



    /**
     * Check the MUST attributes for the given object according to its LDAP
     * objectClass. If all MUST attributes are satisfied checkMust() will return
     * a boolean true, otherwise it returns the offending attribute name.
     * @param type $instance
     * @return TRUE or the name of the offending attribute
     */
    public function checkMust($instance) {
        $classMetaData = $this->getClassMetaData(get_class($instance));

        foreach ($classMetaData->getMust() as $mustAttributeName => $existence) {
            $getter = 'get'.ucfirst($mustAttributeName);
            $value = $instance->$getter();
            if (empty($value)) {
                throw new MissingMustAttributeException($mustAttributeName);
            }
        }
        return true;
    }


    /**
     * Persist an array using ldap function
     *
     * @param unknown_type $dn
     * @param array        $entry
     */
    private function ldapPersist($dn, LdapEntity $entity)
    {
        // Connect if needed
        $this->connect();

        $entry = $this->entityToEntry($entity);

        list($toInsert,) = $this->splitArrayForUpdate($entry);
        //$operands = $this->getEntityOperands($entry);

        $this->logger->debug("Insert $dn in LDAP : " . json_encode($toInsert));
        return ldap_add($this->ldapResource, $dn, $toInsert);
    }

    /**
     * Look at an entry's attributes and determine, relative to it's state before being modified,
     * the operation for each attribute.
     */
    private function getEntityOperands(LdapEntity $original, LdapEntity $modified, $notRetrievedAttributes = [], $operationalAttributes = []) {
        $operands = [ self::OPERAND_MOD => [], self::OPERAND_DEL => [], self::OPERAND_ADD => [] ];
        $modifiedEntry = $this->entityToEntry($modified);
        $originalEntry = $this->entityToEntry($original);

        // Do not attempt to modify operational attributes
        foreach($operationalAttributes as $operationalAttributeName => $status) {
            if ($status) {
                unset($modifiedEntry[$operationalAttributeName]);
            }
        }

        // Do not attempt to modify restricted attributes
        unset($modifiedEntry['objectClass']);
        unset($modifiedEntry['uid']);
        unset($modifiedEntry['employeeId']);
        unset($modifiedEntry['ucsfEduIDNumber']);
        unset($modifiedEntry['dn']);
        unset($modifiedEntry['cn']);
        unset($modifiedEntry['distinguishedName']);
        unset($modifiedEntry['name']);
        unset($modifiedEntry['instanceType']);
        unset($modifiedEntry['sAMAccountType']);

        // Inspect the state of each attribute and determinal if this is to be persisted as an attribute
        // modification, deletion or addition.
        foreach($modifiedEntry as $attribute => $value) {
            // Don't include attributes that haven't actually changed
            if ($value == $originalEntry[$attribute]) {
                continue;
            }
            // If the modified value is empty, first make sure it was an attribute that was originall
            // retrieved. If so, set the delete operations to use the original value.
            if (is_null($value) || (empty($value) && $value !== 0 &&  $value !== FALSE)) {
                if (!in_array($attribute, $notRetrievedAttributes)) {
                    $operands[self::OPERAND_DEL][$attribute] = $originalEntry[$attribute];
                }
                // If modified is not the same value as the original, and it's not empty, if must be a real modify
            } else {
                if (is_array($value)) {
                    $value = $this->getEntityOperands($value)[self::OPERAND_MOD];
                } elseif($value instanceof \Datetime) { // It shouldn't happen, but tests did reveal such cases
                    $value = new DateTimeDecorator($value);
                }
                $operands[self::OPERAND_MOD][$attribute] = $value;
            }
        }
        return $operands;
    }


    /**
     * Splits modified and removed attributes and make sure they are compatible with ldap_modify & insert
     *
     * @param array        $entry
     *
     * @return array
     */
    private function splitArrayForUpdate($entry, $currentEntity = null)
    {
        $toModify = array_filter(
            $entry,
            function ($elm) { // removes NULL, FALSE and '' ; keeps everything else (like 0's)
                return !is_null($elm) && $elm!==false && $elm!=='';
            }
        );

        $toDelete = array_fill_keys(array_keys(array_diff_key($entry, $toModify)), array());
        if ($currentEntity != null) {
            $currentEntry = $this->entityToEntry($currentEntity);
            foreach (array_keys($entry) as $key) {
                if (empty($entry[$key]) && empty($currentEntry[$key])) {
                    unset($toDelete[$key]);
                }
            }
        }

        foreach ($toModify as &$val) {
            if (is_array($val)) {
                list($val,) = $this->splitArrayForUpdate($val); // Multi-dimensional arrays are also fixed
            }
            elseif(is_string($val)) {
                // $val = utf8_encode($val);
            }
            elseif($val instanceof \Datetime) { // It shouldn't happen, but tests did reveal such cases
                $val = new DateTimeDecorator($val);
            }
        }
        return array(array_merge($toModify), array_merge($toDelete)); // array_merge is to re-index gaps in keys
    }

    /**
     * Update an object in ldap with array
     *
     * @param $dn
     * @param LdapEntity $modified
     * @param LdapEntity $original
     * @throws Exception
     */
    private function ldapUpdate($dn, LdapEntity $modified, LdapEntity $original)
    {
        $updated = FALSE;
        $this->connect();

        $notRetrievedAttributes = $modified->getNotRetrieveAttributes();
        $operationalAttributes = $this->getClassMetadata($modified)->getOperational();
        $operands = $this->getEntityOperands($original, $modified, $notRetrievedAttributes, $operationalAttributes);

        if (!empty($operands[self::OPERAND_MOD])) {
            if (ldap_modify($this->ldapResource, $dn, $operands[self::OPERAND_MOD])); {
                $updated = TRUE;
            }
            $this->logger->debug('MODIFY: "'.$dn.'" "'.json_encode($operands[self::OPERAND_MOD]).'"');
        }

        if (!empty($operands[self::OPERAND_DEL])) {
            try {
                if (ldap_mod_del($this->ldapResource, $dn, $operands[self::OPERAND_DEL])) {
                    $updated = TRUE;
                }
                $this->logger->debug('DELETE: "' . $dn . '" "' . json_encode($operands[self::OPERAND_DEL]) . '"');
            } catch (\Exception $e) {
                // ldap_mod_del() will fail if it tries to delete an attribute
                // which is not present on the target entry. This is not an error.
                // PHP will say: "Warning: ldap_mod_del(): Modify: No such attribute"
                // This just effective ignores it.
                $this->logger->debug('DELETE (not present): "' . $dn . '" "' . json_encode($operands[self::OPERAND_DEL]) . '"');
            }
        }

        return $updated;
    }

    /**
     * The core of ORM behavior for this bundle: retrieve data
     * from LDAP and convert results into objects.
     *
     * Options maybe:
     *
     * attributes (array): array of attribute types (strings)
     * filter (LdapFilter): a filter array or a correctly formatted filter string
     * max (integer): the maximum limit of entries to return
     * searchDn (string): the search DN
     * subentryNodes (array): parameters for the left hand side of a searchDN, useful for mining subentries.
     * pageSize (integer): employ pagination and return pages of the given size
     * pageCookie (opaque): The opaque stucture sent by the LDAP server to maintain pagination state. Defaults is empty string.
     * pageCritical (boolean): if pagination employed, force paging and return no results on service which do not provide it. Default is true.
     * checkOnly (boolean): Only check result existence; don't convert search results to Symfony entities. Default is false.
     *
     * @param type $entityName
     * @param type $options
     * @return type
     */
    public function retrieve($entityName, $options = array())
    {
        $paging = !empty($options['pageSize']);

        $instanceMetadataCollection = $this->getClassMetadata($entityName);
        $metaDatas = $instanceMetadataCollection->getMetadatas();
        $mustAttributes = $instanceMetadataCollection->getMust();

        // Discern max result size
        $max = empty($options['max']) ? self::DEFAULT_MAX_RESULT_COUNT : $options['max'];

        // Employ results paging if requested with pageSize option
        if ($paging) {
            if (!isset($options['pageCritical'])) {
                $options['pageCritical'] = FALSE;
            }
            if (isset($options['pageCookie'])) {
                $this->pageCookie = $options['pageCookie'];
            }

            $this->connect();
            ldap_control_paged_result($this->ldapResource, $options['pageSize'], $options['pageCritical'], $this->pageCookie);
        }

        // Discern subentryNodes for substituing into searchDN
        $subentryNodes = empty($options['subentryNodes']) ? array() : $options['subentryNodes'];

        // Discern search DN
        if (isset($options['searchDn'])) {
            $searchDn = $options['searchDn'];
        } else {
            $searchDn = $this->twigRender($instanceMetadataCollection->getSearchDn(), ['entity' => $subentryNodes]);
        }

        if (empty($searchDn)) {
            // throw new MissingSearchDn('Could not discern search DN while searching for '.$entityName);
            $searchDn = '';
        }

        // Discern LDAP filter
        $objectClass = $instanceMetadataCollection->getObjectClass();
        if (empty($options['filter'])) {
            $filter = '(objectClass='.$objectClass.')';
        } else {
            if (is_array($options['filter'])) {
                $options['filter'] = array(
                    '&' => array(
                        'objectClass' => $objectClass,
                        $options['filter']
                    )
                );
                $ldapFilter = new LdapFilter($options['filter'], $this->isActiveDirectory);
                $filter = $ldapFilter->format();
            } else if (is_a ($options['filter'], LdapFilter::class)){
                $options['filter']->setFilterArray(
                    array(
                        '&' => array(
                            'objectClass' => $objectClass,
                            $options['filter']->getFilterArray()
                        )
                    )
                );
                $filter = $options['filter']->format();
            } else { // assume pre-formatted scale/string filter value
                $filter = '(&(objectClass='.$objectClass.')'.$options['filter'].')';
            }
        }

        // Discern attributes to retrieve. If no attributes are supplied, get all the variables annotated
        // as LDAP attributes within the entity class
        $attributes = empty($options['attributes']) ? array_values($metaDatas) : $options['attributes'];
        // Always get MUST attributes because they might be needed later when persisting
        $attributes = array_values(array_unique(array_merge($attributes, array_keys(array_filter($mustAttributes)))));
        $notRetrieveAttributes = array_diff(array_values($metaDatas), $attributes);

        // Search LDAP
        $searchResult = $this->doRawLdapSearch($filter, $attributes, $max, $searchDn);


        $entries = ldap_get_entries($this->ldapResource, $searchResult);
        $this->logger->debug('SEARCH: "'.$entries['count'].'" "'.$searchDn.'" "'.$filter.'"');
        if (!empty($options['checkOnly']) && $options['checkOnly'] == true) {
            return ($entries['count'] > 0);
        }
        $entities = array();
        foreach ($entries as $entry) {
            if(is_array($entry)) {
                $entity = $this->entryToEntity($entityName, $entry);
                $entity->setNotRetrieveAttributes($notRetrieveAttributes);
                $entities[] = $entity;
            }
        }

        if ($paging) {
            ldap_control_paged_result_response($this->ldapResource, $searchResult, $this->pageCookie);
            $this->pageMore = !empty($this->pageCookie);
        }

        return $entities;
    }

    /**
     * Get the PHP LDAP pagination cookie
     * @return string
     */
    public function getPageCookie()
    {
        return $this->pageCookie;
    }

    /**
     * Check if the results pager has more results to return
     * @return boolean
     */
    public function pageHasMore()
    {
        return $this->pageMore;
    }



    /**
     * retrieve object from dn
     *
     * @param string     $dn
     * @param string     $entityName
     * @param integer    $max
     *
     * @return array
     */
    public function retrieveByDn($dn, $entityName, $max = self::DEFAULT_MAX_RESULT_COUNT, $objectClass = "*")
    {
        // Connect if needed
        $this->connect();

        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $data = array();
        $this->logger->debug('SEARCH-By-DN: ' . $dn . ' query (ObjectClass=*)');
        try {
            $sr = ldap_search($this->ldapResource,
                $dn,
                '(ObjectClass=' . $objectClass . ')',
                array_values($instanceMetadataCollection->getMetadatas()),
                0
            );
            $infos = ldap_get_entries($this->ldapResource, $sr);
            foreach ($infos as $entry) {
                if(is_array($entry)) {
                    $data[] = $this->entryToEntity($entityName, $entry);
                }
            }
        } catch(Exception $e) {
            $data = array();
        }

        return $data;
    }

    public function doRawLdapGetDn($rawResult)
    {
        return ldap_get_dn($this->ldapResource, $rawResult);
    }

    public function doRawLdapGetAttributes($rawResult)
    {
        return ldap_get_attributes($this->ldapResource, $rawResult);
    }

    public function doRawLdapCountEntries($rawResult)
    {
        return ldap_count_entries($this->ldapResource, $rawResult);
    }

    public function doRawLdapFirstEntry($rawResult)
    {
        return ldap_first_entry($this->ldapResource, $rawResult);
    }

    public function doRawLdapNextEntry($rawResult)
    {
        return ldap_next_entry($this->ldapResource, $rawResult);
    }

    /**
     * @param $rawFilter
     * @param $attributes
     * @param $count
     * @param $searchDN
     * @return resource
     * @throws \Exception
     */
    public function doRawLdapSearch($rawFilter, $attributes, $count, $searchDN)
    {
        $this->connect();
        $this->logger->debug(sprintf("request on ldap root:%s with filter:%s", $searchDN, $rawFilter));
        return ldap_search($this->ldapResource,
            $searchDN,
            $rawFilter,
            $attributes,
            0);
    }

    /**
     * @param LdapFilter $filter
     * @param $entityName
     * @return null|LdapIterator
     */
    public function getIterator(LdapFilter $filter, $entityName) {
        if (empty($this->iterator)) {
            $this->iterator = new LdapIterator($filter, $entityName, $this);
        }
        return $this->iterator;
    }


    public function cleanArray($array)
    {
        $newArray = array();
        foreach(array_keys($array) as $key) {
            $newArray[strtolower($key)] = $array[$key];
        }

        return $newArray;
    }


    /**
     * @param $entityName
     * @param $entryData
     * @return LdapEntity
     */
    public function entryToEntity($entityName, $entryData)
    {

        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $entryData = $this->cleanArray($entryData);
        $dn = $entryData['dn'];
        $entity = new $entityName();
        $metaDatas = $instanceMetadataCollection->getMetadatas();

        // The 'cn' attribite is at the heart of LDAP entries and entities and is often required for
        // many other processes. Make this this gets applied from the entry to the entity first.
        if (!empty($entryData['cn'][0])) {
            $entity->setCn($entryData['cn'][0]);
        }
        foreach($metaDatas as $attrName => $attrValue) {
            $attrValue = strtolower($attrValue);
            if($instanceMetadataCollection->isArrayOfLink($attrName))
            {
                $entityArray = array();
                if(!isset($entryData[$attrValue])) {
                    $entryData[$attrValue] = array('count' => 0);
                }
                $linkArray = $entryData[$attrValue];
                $count = $linkArray['count'];
                for($i = 0; $i < $count; $i++) {
                    if($linkArray[$i] != null) {
                        $targetArray = $this->retrieveByDn($linkArray[$i], $instanceMetadataCollection->getArrayOfLinkClass($attrName), 1);
                        $entityArray[] = $targetArray[0];
                    }
                }
                $setter = 'set' . ucfirst($attrName);
                $entity->$setter($entityArray);
            } else {
                $setter = 'set' . ucfirst($attrName);
                if (!isset($entryData[$attrValue])) {
                    continue; // Don't set the atribute if not exit
                }
                try {
                    if(preg_match('/^\d{14}/', $entryData[$attrValue][0])) {
                        if ($this->isActiveDirectory) {
                            $datetime = Util::adDateToDatetime($entryData[$attrValue][0]);
                        } else {
                            $datetime = Converter::fromLdapDateTime($entryData[$attrValue][0], false);
                        }
                        $entity->$setter($datetime);
                    } elseif ($instanceMetadataCollection->isArrayField($attrName)) {
                        unset($entryData[$attrValue]["count"]);
                        $entity->$setter($entryData[$attrValue]);
                    } else {
                        $entity->$setter($entryData[$attrValue][0]);
                    }
                } catch (Exception $e) {
                    $this->logger->error(sprintf("Exception in ldap to entity mapping : %s", $e->getMessage()));
                }
            }
        }
        foreach($instanceMetadataCollection->getDnRegex() as $attrName => $regex) {
            preg_match_all($regex, $entryData['dn'], $matches);
            $setter = 'set' . ucfirst($attrName);
            $entity->$setter($matches[1]);
        }
        if($dn != '') {
            $entity->setDn($dn);
            foreach($instanceMetadataCollection->getParentLink() as $attrName => $parentClass) {
                $setter = 'set' . ucfirst($attrName);
                $parentDn = preg_replace('/^[a-zA-Z0-9]*=[a-zA-Z0-9]*,/', '', $dn);
                $link = $this->retrieveByDn($parentDn, $parentClass);
                if(count($link) > 0) {
                    $entity->$setter($link[0]);
                }
            }
        }

        return $entity;
    }

    private function generateSequenceValue($dn)
    {
        // Connect if needed
        $this->connect();

        $sr = ldap_search($this->ldapResource,
            $dn,
            '(objectClass=integerSequence)'
        );
        $infos = ldap_get_entries($this->ldapResource, $sr);
        $sequence = $infos[0];
        $return = $sequence['nextvalue'][0];
        $newValue = $sequence['nextvalue'][0] + $sequence['increment'][0];
        $entry = array(
            'nextvalue' => array($newValue),
        );
        ldap_modify($this->ldapResource, $dn, $entry);
        return $return;
    }

    private function isSha1($str) {
        return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
    }

    private function twigRender($templateString = null, $variables = null) {
        if (!$templateString) {
            return FALSE;
        }
        if (!$variables) {
            return $templateString;
        }
        $template = $this->twig->createTemplate($templateString);
        return $template->render($variables);
    }


    public function rename($dn, $newRdn, $newParent, $deleteOld = TRUE) {
        if (!ldap_rename($this->ldapResource, $dn, $newRdn, $newParent, $deleteOld)) {
            throw new RenameException(ldap_error($this->ldapResource));
        }
        return TRUE;
    }
}

class MissingMustAttributeException extends \Exception {}

class MissingSearchDn extends \Exception {}

class RenameException extends \Exception {}
