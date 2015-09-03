<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;


class LdapEntity implements \JsonSerializable {
    
    
    const EDS = 'directory';
    const SOMAD = 'somad';
    const MCAD = 'medcenterad';
    const CAMPUSAD = 'campusad';
    
    public function __construct() {
        $this->setObjectClass(lcfirst((new \ReflectionClass(get_class($this)))->getShortName()));
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
    
}
