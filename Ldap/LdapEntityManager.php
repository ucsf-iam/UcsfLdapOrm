<?php
/**
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */

namespace Ucsf\LdapOrmBundle\Ldap;

use Doctrine\Common\Annotations\Reader;
use Twig\Environment;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Dn;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Repository as RepositoryAttribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Operational;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Sequence;
use Ucsf\LdapOrmBundle\Annotation\Ldap\UniqueIdentifier;
use Ucsf\LdapOrmBundle\Entity\DateTimeDecorator;
use Ucsf\LdapOrmBundle\Entity\Ldap\LdapEntity;
use Ucsf\LdapOrmBundle\Ldap\Filter\LdapFilter;
use Ucsf\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Ucsf\LdapOrmBundle\Repository\Repository;
use Symfony\Bridge\Monolog\Logger;

/**
 * Entity Manager for LDAP
 *
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class LdapEntityManager
{
    const DEFAULT_MAX_RESULT_COUNT      = 20000;
    const OPERAND_ADD = 'add';
    const OPERAND_MOD = 'mod';
    const OPERAND_DEL = 'del';

    protected $uri        	= "";
    protected $bindDN     	= "";
    protected $password   	= "";
    protected $passwordType 	= "";
    protected $useTLS     	= FALSE;
    protected $isActiveDirectory = FALSE;
    protected $followReferrals = 0;

    protected $ldapResource;
    protected $pageCookie 	= "";
    protected $pageMore    	= FALSE;
    protected $reader;

    protected $iterator = Null;

    /**
     * LdapEntityManager constructor.
     *
     * The $options array can contain the following indexes, otherwise these are the defaults:
     *  uri: <required, no default>
     *  bind_dn: <required, no default>
     *  password: <required, no default>
     *  password_type: "plaintext" (string)
     *  use_tls: FALSE (boolean)
     *  active_directory:  FALSE (boolean)
     *
     *
     * @param Logger $logger
     * @param \Twig_Environment $twig
     * @param Reader $reader
     * @param $config
     */
    public function __construct(Logger $logger, Reader $reader, Environment $twig, array $config=[])
    {
        $this->logger     	        = $logger;
        $this->reader     	        = $reader;
        $this->twig                 = $twig;
        $this->setConfig($config);
    }


    /**
     * A more manual inroad to manipulating the configuration
     *
     * @param array $config
     */
    public function setConfig(array $config) : void
    {
        $this->uri        	        = $config['uri'] ?? null;
        $this->bindDN     	        = $config['bind_dn'] ?? null;
        $this->password   	        = $config['password'] ?? null;
        $this->passwordType         = $config['password_type'] ?? 'plaintext';
        $this->useTLS     	        = $config['use_tls'] ?? false;
        $this->isActiveDirectory    = $config['active_directory'] ?? false;
    }


    /**
     * Return an array of the following configuration values:
     *
     *  'uri'               => $this->uri,
     *  'bind_dn'           => $this->bindDN,
     *  'password'          => $this->pssword,
     *  'password_type'     => $this->passwordType,
     *  'use_tls'           => $this->useTLS,
     *  'active_directory'  => $this->isActiveDirectory
     *
     * @return array as described above
     */
    public function getConfig() :array
    {
        return [
            'uri'               => $this->uri,
            'bind_dn'           => $this->bindDN,
            'password'          => $this->pssword,
            'password_type'     => $this->passwordType,
            'use_tls'           => $this->useTLS,
            'active_directory'  => $this->isActiveDirectory
        ];
    }


    /**
     * Manually set the LDAP_OPT_REFERRALS option for the next connect().
     *
     * @param int $state
     * @throws \Exception
     */
    public function setFollowReferrals(int $state) : void
    {
        if ($this->followReferrals !== $state) {
            $this->disconnect();
            $this->followReferrals = $state;
            $this->connect();
        }
    }


    /**
     * A wrapper for ldap_close()
     */
    public function disconnect() : void
    {
        if ($this->ldapResource) {
            ldap_close($this->ldapResource);
            $this->ldapResource = null;
        }
    }


    /**
     * Connect to LDAP service
     *
     * @return LDAP resource or FALSE upon error
     */
    protected function connect()
    {
        // Don't permit multiple connect() calls to run
        if ($this->ldapResource) {
            return;
        }

        $this->ldapResource = ldap_connect($this->uri);
        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, $this->followReferrals);

        // Switch to TLS, if configured
        if ($this->useTLS) {
            $tlsStatus = ldap_start_tls($this->ldapResource);
            if (!$tlsStatus) {
                throw new \Exception('Unable to enable TLS for LDAP connection.');
            }
            $this->logger->debug('TLS enabled for LDAP connection.');
        }

        // Give 3 tries to connect
        for ($try=1; $try<4; $try++) {
            $msg = '';
            try {
                $bindResult = ldap_bind($this->ldapResource, $this->bindDN, $this->password);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
            }
            if (!empty($bindResult)) {
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
     *
     * @param $entity The entity to check for existance. Entity must have all MAY attributes.
     * @return bool Returns true if the given entity exists in LDAP
     */
    public function entityExists($entity, $checkOnly = true) {
        $this->checkMust($entity);
        $entityClass = get_class($entity);

        $searchDn = LdapEntity::getBaseDnFromDn($entity->getDn());
        $uniqueIdentifier = $this->getUniqueIdentifier($entity);

        $entities = $this->retrieve($entityClass, [
            'searchDn' => $searchDn,
            'filter' => [ $uniqueIdentifier['attribute'] => $uniqueIdentifier['value'] ]
        ]);

        if (count($entities) > 1) {
            throw new \Exception('Multiple entities found for supposedly unique DN of "'.$entity->getDn().'"');
        }

        if ($checkOnly) {
            return (count($entities) > 0);
        } else {
            return (count($entities) > 0) ? $entities[0] : false;
        }
    }

    /**
     * Get the unique identifier value from the attribute describes in the entity's @UniqueIdentifier() annotation.
     *
     * @param LdapEntity $entity
     * @param bool $throwExceptions
     * @return array
     * @throws \Exception
     */
    public function getUniqueIdentifier(LdapEntity $entity, $throwExceptions = true) {
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
     * @return ClassMetaDataCollection
     * @throws \ReflectionException
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
                if ($annotation instanceof Sequence) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addSequence($varname, $annotation->getValue());
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


    public function entityToLdif(LdapEntity $entity) {
        $entityName = get_class($entity);
        $metadata = $this->getClassMetadata($entityName);

        $objectClasses = '';
        $ldif = '';
        foreach($metadata->getMetadatas() as $name) {
            $getter = 'get'.$name;


            $finalVal = '';
            if ($val = $entity->$getter()) {
                if (is_array($val)) {
                    foreach ($val as $subval) {
                        $finalVal .= $name . ': ' . $subval . "\n";
                    }
                } else {
                    $finalVal .= $name . ': ' . $val . "\n";
                }
            }
            if (strtolower($name) == 'objectclass') {
                $objectClasses .= $finalVal;
            } else {
                $ldif .= $finalVal;
            }
        }

        $ldif = "version:1 \n\n".$objectClasses.$ldif;

        $x = 1;
    }


    /**
     * Convert an entity to a PHP OpenLdap array data structure.
     *
     * @param LdapEntity $entity
     * @return array A PHP OpenLdap array data structure
     * @throws \ReflectionException
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

        foreach ($metadata->getMetadatas() as $attribute) {
            $getter = 'get' . ucfirst($metadata->getKey($attribute));
            $setter = 'set' . ucfirst($metadata->getKey($attribute));

            $value  = $entity->$getter();
            if ($value == null) {
                if ($metadata->isSequence($metadata->getKey($attribute))) {


                    $sequence = $this->twigRender(
                        $metadata->getSequence($metadata->getKey($attribute)),
                        [ 'entity' => $entity ]
                    );

                    $value = (int) $this->generateSequenceValue($sequence);
                    $entity->$setter($value);
                }
            }

            // Ldap doesn't have so many boolean-esque types like PHP, so convert to "TRUE" or "FALSE".
            if (is_bool($value)) {
                if ($value) {
                    $value = "TRUE";
                } else {
                    $value = "FALSE";
                }
            }

            if (is_object($value)) {
                if ($value instanceof \DateTime) {
                    $entry[$attribute] = Util::datetimeToAdDate($value);
                }
                elseif ($value instanceof DateTimeDecorator) {
                    $entry[$attribute] = (string)$value;
                }
                else {
                    $entry[$attribute] = $this->buildEntityDn($value);
                }
            } elseif (is_array($value) && !empty($value) && isset($value[0]) && is_object($value[0])) {
                $valueArray = array();
                foreach ($value as $val) {
                    $valueArray[] = $this->buildEntityDn($val);
                }
                $entry[$attribute] = $valueArray;
            } elseif (strtolower($attribute) == "userpassword") {
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
     * @param $instance An instance of LdapEntity (or sub-class)
     * @return bool|null|string
     * @throws \ReflectionException
     */
    public function buildEntityDn(LdapEntity $instance)
    {
        $instanceClassName = get_class($instance);
        $r = new \ReflectionClass($instanceClassName);
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


    /**
     * "Upsert" an LDAP entity to LDAP.
     *
     * Using the $entityExists parameter is not necessary, but can save unnecessary calls to LDAP. If not specified
     * this method will attempt to retrieve the record from AD to determine whether or not it already exists so that
     * it can determine if an insert or an update is required. If the value is true, the method will use the given
     * entity as the source for the record to use in ldap_modify/ldap_mod_del, without checking. If false, an ldap_add
     * will be performed with no checking.
     *
     * @param $entity The entity representing the LDAP record to persist
     * @param null $entityExists Does this entity already exist in LDAP? (Is this an update?)
     * @param bool $checkMust If true, ensure that record will have data for all MUST attributes
     * @param array $clearedAttributes List the attributes that have been cleared compared to the current state in LDAP
     * @return bool True if persistence was successfull.
     * @throws MissingMustAttributeException
     * @throws \ReflectionException
     */
    public function persist($entity, $entityExists = null, $checkMust = true, $clearedAttributes = [])
    {
        if ($checkMust) {
            $this->checkMust($entity);
        }

        $dn = $entity->getDn();
        if ($dn == NULL) {
            $dn = $this->buildEntityDn($entity);
            $entity->setDn($dn);
        }

        // If ldapPersist() is not forced through the $entityExists parameter, check whether or not the
        // entry exists.
        if ($entityExists !== FALSE) {
            $entityExists = $this->entityExists($entity, false);
        }


        if ($entityExists === FALSE) {
            return $this->ldapPersist($dn, $entity);
        } else {
            return $this->ldapUpdate($dn, $entity, $entityExists, $clearedAttributes);
        }
    }


    /**
     * Delete an entity's record in LDAP
     *
     * @param $entity
     * @throws \ReflectionException
     */
    public function delete(LdapEntity $entity)
    {
        $this->logger->debug('Delete in LDAP: ' . $entity->getDn() );
        $this->deleteByDn($entity->getDn(), true);
        return;
    }


    /**
     * Delete an entry in ldap by Dn.
     *
     * The recursive algorithm was coped from: https://www.php.net/manual/en/function.ldap-delete.php
     *
     * @param string The
     */


    /**
     * Delete an entry in ldap by Dn.
     *
     * The recursive algorithm was coped from: https://www.php.net/manual/en/function.ldap-delete.php
     *
     * @param $dn
     * @param bool $recursive
     * @return mixed Returns the distinguished name of the deleted record or false if there was an error.
     * @throws \Exception
     */
    public function deleteByDn($dn, $recursive=false)
    {
        $this->connect();
        $this->logger->debug('Delete (recursive=' . $recursive . ') in LDAP: ' . $dn );

        if ($recursive != false) {
            // Find sub-entries of the current level
            $sr=ldap_list($this->ldapResource, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($this->ldapResource, $sr);
            // Delete sub-entries recursively
            for($i = 0; $i < $info['count']; $i++) {
                return $this->deleteByDn($info[$i]['dn'], true);
            }
        }

        try {
            ldap_delete($this->ldapResource, $dn);
            $r = $dn;
        } catch (\Exception $e) {
            $errno = ldap_errno($this->ldapResource);
            $error = ldap_error($this->ldapResource);
            $errstr = ldap_err2str($errno);
            $r = false;
        }

        return $r;
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
        if ($metadata->getRepository()) {
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
    protected function ldapPersist($dn, LdapEntity $entity)
    {
        $this->connect();
        $entry = $this->entityToEntry($entity);
        list($toInsert,) = $this->splitArrayForUpdate($entry);
        // The dn is already specific in the ldap_add() call. Keeping it in $toInsert will cause an
        // Object Class Violation or other DN-related violations.
        unset($toInsert['dn']);
        unset($toInsert['distinguishedName']);
        $this->logger->debug("Insert $dn in LDAP : " . json_encode($toInsert));
        $r = false;
        try {
            $r = ldap_add($this->ldapResource, $dn, $toInsert);
        } catch (\Exception $e) {
            $errno = ldap_errno($this->ldapResource);
            $errstr = ldap_err2str($errno);
        }

        return $r;
    }

    /**
     * Look at an entry's attributes and determine, relative to it's state before being modified,
     * the operation for each attribute.
     */
    protected function getEntityOperands(LdapEntity $original, LdapEntity $modified, $notRetrievedAttributes = [], $operationalAttributes = []) {
        $operands = [ self::OPERAND_MOD => [], self::OPERAND_DEL => [], self::OPERAND_ADD => [] ];
        $modifiedEntry = $this->entityToEntry($modified);
        $originalEntry = $this->entityToEntry($original);

        // Do not attempt to modify operational attributes
        foreach ($operationalAttributes as $operationalAttributeName => $status) {
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
        foreach ($modifiedEntry as $attribute => $value) {
            // Don't include attributes that haven't actually changed
            if ($value == $originalEntry[$attribute]) {
                continue;
            }
            // If the modified value is empty, first make sure it was an attribute that was originall
            // retrieved. If so, set the delete operations to use the original value.
            if (is_null($value) || (!is_array($value) && empty($value) && $value !== 0 &&  $value !== FALSE)) {
                if (!in_array($attribute, $notRetrievedAttributes)) {
                    $operands[self::OPERAND_DEL][$attribute] = $originalEntry[$attribute];
                }
                // If modified is not the same value as the original, and it's not empty, if must be a real modify
            } else {
                if ($value instanceof \Datetime) {
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
    protected function splitArrayForUpdate($entry, $currentEntity = null)
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
            elseif (is_string($val)) {
                // $val = utf8_encode($val);
            }
            elseif ($val instanceof \Datetime) { // It shouldn't happen, but tests did reveal such cases
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
    protected function ldapUpdate($dn, LdapEntity $modified, LdapEntity $original, $clearedAttributes = [])
    {
        $updated = FALSE;
        $this->connect();

        $notRetrievedAttributes = $modified->getNotRetrieveAttributes();
        $notRetrievedAttributes = array_diff($notRetrievedAttributes, $clearedAttributes);
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

        // Discern search DN
        $searchDn = $options['searchDn'] ?: '';

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
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }
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
            if (is_array($entry)) {
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
                if (is_array($entry)) {
                    $data[] = $this->entryToEntity($entityName, $entry);
                }
            }
        } catch(Exception $e) {
            $data = array();
        }

        return $data;
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
        if ($count) {
            return @ldap_search($this->ldapResource,
                $searchDN,
                $rawFilter,
                $attributes,
                0,
                $count);
        } else {
            return ldap_search($this->ldapResource,
                $searchDN,
                $rawFilter,
                $attributes,
                0,
                $count);
        }
    }


    /**
     * Convert OpenLDAP entries to entity objects
     *
     * @param $entityName
     * @param $entryData
     * @return mixed
     * @throws \ReflectionException
     */
    public function entryToEntity($entityName, $entryData)
    {
        // Normalize $entryData to lower case
        foreach ($entryData as $key => $val) {
            unset($entryData[$key]);
            $entryData[strtolower($key)] = $val;
        }

//        $dn = $entryData['distinguishedname'][0] ?? '';
        $dn = $entryData['dn'] ?? '';
        $entity = new $entityName();

        $instanceMetadataCollection = $this->getClassMetadata($entityName);
        $metaDatas = $instanceMetadataCollection->getMetadatas();

        // The 'cn' attribite is at the heart of LDAP entries and entities and is often required for
        // many other processes. Make this this gets applied from the entry to the entity first.
        if (!empty($entryData['cn'][0])) {
            $entity->setCn($entryData['cn'][0]);
        }
        foreach ($metaDatas as $attrName => $attrValue) {
            $attrValue = strtolower($attrValue);
            if ($instanceMetadataCollection->isArrayOfLink($attrName))
            {
                $entityArray = array();
                if (!isset($entryData[$attrValue])) {
                    $entryData[$attrValue] = array('count' => 0);
                }
                $linkArray = $entryData[$attrValue];
                $count = $linkArray['count'];
                for($i = 0; $i < $count; $i++) {
                    if ($linkArray[$i] != null) {
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
                    if (preg_match('/^\d{14}/', $entryData[$attrValue][0])) {
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
        foreach ($instanceMetadataCollection->getDnRegex() as $attrName => $regex) {
            preg_match_all($regex, $entryData['dn'], $matches);
            $setter = 'set' . ucfirst($attrName);
            $entity->$setter($matches[1]);
        }

        if (empty($entity->getDistinguishedName())) {
            $entity->setDistinguishedName($dn);
        }

        return $entity;
    }

    protected function generateSequenceValue($dn)
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

    protected function isSha1($str) {
        return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
    }

    protected function twigRender($templateString = null, $variables = null) {
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

    /**
     * Add an object to a group. If the object was successfully added or if the object was already a member of the group
     * consider both circumstances are being successfully added.  (Note that PHP's ldap_mod_add() returns FALSE when
     * an add attempt is made and the object is already in the group. Unfortunately, PHP's documentation is ambiguous
     * regarding under what circumstances the FALSE is returned, so all of this extra code to find out the resulting
     * state of the object and the group when ldap_mod_add() returns FALSE is for combining those two "successful cases.)
     * @param $groupDn The distinguished name of the group to add the object to
     * @param $memberDn The distinguished name of the object to add to the group
     * @return bool Returns TRUE when the object is successfully added to the group or was already a member.
     * @throws \Ucsf\LdapOrmBundle\Exception\Filter\InvalidLdapFilterException
     */
    public function groupAdd($groupDn, $memberDn) {
        $this->connect();

        $groupInfo['member'] = $memberDn;
        $result = @ldap_mod_add($this->ldapResource, $groupDn, $groupInfo);

        if (!$result) {
            // The FALSE result might be because the user is already in the group. Check to see if it is in the group
            // despite this result from ldap_mod_add(). This attempts to retrieve the object for adding by looking
            // for this object as though it had a memberOf attribute value of the group in question.
            $memberRdns = ldap_explode_dn($memberDn, 1);
            $searchDn = preg_replace('/^.*?dc=/i', 'dc=', $memberDn);
            $ldapFilter = new LdapFilter(
                [
                    '&' => [
                        'cn' => $memberRdns[0],
                        'memberOf' => $groupDn
                    ]
                ],
                $this->isActiveDirectory
            );
            // Revert referral following for a search...
            $rawResult = $this->doRawLdapSearch($ldapFilter->format(), ['member'], null, $searchDn );
            $entries = ldap_get_entries($this->ldapResource, $rawResult);

            // If the object was not found as part of the group, and we've already received a FALSE from ldap_mod_add(),
            // then we have a real problem. Otherwise, if the object was found we know it's already a group member
            // and return TRUE
            if ($entries['count'] < 1) {
                $err = ldap_error($this->ldapResource);
                throw new \Exception('Unable to add "' . $memberDn . '" to group "' . $groupDn . '"": ' . $err);
            } else {
                $result = TRUE;
            }
        }

        return $result;
    }



    public function groupRemove($groupDn, $memberDn) {
        $this->connect();

        $groupInfo['member'] = $memberDn;
        $result = @ldap_mod_del($this->ldapResource, $groupDn, $groupInfo);

        if (!$result) {
            $err = ldap_error($this->ldapResource);
            // The err msg condition happens when the server would not remove the item from the group because it wasn't
            // already present. This is not really an error, so only throw if the result is FALSE but the err msg is
            // something else... likely more egregious.
            if ($err != 'Server is unwilling to perform') {
                throw new \Exception('Unable to remove "' . $memberDn . '" from group "' . $groupDn . '"": ' . $err);
            }
        }

        return $result;
    }


    public function isActiveDirectory() {
        return $this->isActiveDirectory;
    }
}

class MissingMustAttributeException extends \Exception {}

class MissingSearchDn extends \Exception {}

class RenameException extends \Exception {}
