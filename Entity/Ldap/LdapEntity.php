<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;

class LdapEntity implements \JsonSerializable {

    /**
     * A list of attributes which were already empty when the entry was first retrieve.
     * @var $notRetrieveAttributes
     */
    protected $notRetrieveAttributes;

    /**
     * LdapEntity constructor.
     */
    public function __construct() {
        $this->setObjectClass(lcfirst((new \ReflectionClass(get_class($this)))->getShortName()));
    }

    
    /**
     * @return array
     */
    public function getClassAnnotations() {
        $reader = new AnnotationReader();
        return $reader->getClassAnnotations(new \ReflectionClass(self::class));
    }


    /**
     * Sometimes LDAP returns broken UTF-8 data that breaks JSON serialization
     * which requires good UTF-8 input. This will ensure that objects which
     * extend LdapEntity provide good UTF-8 input to json_encode()
     * @return type
     */
    public function jsonSerialize() {
        $encoded = array();
        foreach (get_object_vars($this) as $objectVarName => $objectVarValue) {
            if (empty($objectVarValue)) {
                continue;
            }
            if (is_array($objectVarValue)) {
                $encoded[$objectVarName] = array();
                foreach($objectVarValue as $arrayKey => $arrayValue) {
                    $encoded[$objectVarName][$arrayKey] = json_encode($arrayValue);
                }
            } else if (is_scalar($objectVarValue)) {
                $encoded[$objectVarName] = utf8_encode($objectVarValue);
            } else {
                $encoded[$objectVarName] = json_encode($objectVarValue);
            }
        }
        return $encoded;
    }


    /**
     * @return mixed
     */
    public function getDomain() {
        if (!isset($this->domain)) {
            $this->setDomain();
        }
        return $this->domain;
    }


    /**
     * @param null $domain
     */
    protected function setDomain($domain = null) {
        if ($domain) {
            $this->domain = $domain;
        } else {
            if (!isset($this->domain) && isset($this->dn)) {
                $this->domain = $this->getDomainFromDn($this->dn);
            }
        }
    }


    /**
     * Convert an AD entity's DN into an AD domain name
     * @param $dn
     * @return string
     */
    public static function getDomainFromDn($dn) {
        $dc = preg_split('/,dc=/i', $dn);
        array_shift($dc);
        return strtolower(implode('.', $dc));
    }


    /**
     * @param $dn
     * @return string
     */
    public static function getBaseDnFromDn($dn) {
        return substr($dn, stripos($dn, 'dc='));
    }


    /**
     * @Attribute("objectClass")
     * @Must()
     * @ArrayField()
     */
    protected $objectClass;

    /**
     * @Attribute("cn")
     * @Must()
     */
    protected $cn;

    /**
     * @Attribute("dn")
     */
    protected $dn;
    
    public function getObjectClass() {
        return $this->objectClass;
    }

    public function setObjectClass($objectClasses) {
        $this->objectClass = $objectClasses;
    }

    function getCn() {
        return $this->cn;
    }

    function setCn($cn) {
        $this->cn = $cn;
    }

    public function getDn()
    {
        return $this->dn;
    }

    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * @return array
     */
    public function getNotRetrieveAttributes()
    {
        return $this->notRetrieveAttributes;
    }

    /**
     * @param array $notRetrieveAttributes
     */
    public function setNotRetrieveAttributes(Array $notRetrieveAttributes)
    {
        $this->notRetrieveAttributes = $notRetrieveAttributes;
    }

    public function addNotRetrieveAttribute($name) {
        if (!in_array($name, $this->notRetrieveAttributes)) {
            $this->notRetrieveAttributes[] = $name;
        }
        return $this->getNotRetrieveAttributes();
    }

    public function removeNotRetrievedAttribute($name) {
        foreach ($this->notRetrieveAttributes as $key => $value) {
            if ($value === $name) {
                unset($this->notRetrieveAttributes[$key]);
            }
        }
        return $this->getNotRetrieveAttributes();
    }


}
