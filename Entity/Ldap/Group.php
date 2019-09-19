<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;

/**
 * A superclass for GroupOfEntries and GroupOfNames
 * @author jgabler
 * @ObjectClass("Group")
 */
class Group extends LdapEntity {

    /**
     * @Attribute("businessCategory")
     */
    protected $businessCategory; 

    /**
     * @Attribute("description")
     */
    protected $description;
    
    /**
     * @Attribute("member")
     * @ArrayField()
     */
    protected $member; 

    /**
     * @Attribute("o")
     */
    protected $o; 
    
    /**
     * @Attribute("ou")
     */
    protected $ou;     
    
    /**
     * @Attribute("owner")
     * @ArrayField()
     */
    protected $owner; 
    
    /**
     * @Attribute("seeAlso")
     */
    protected $seeAlso;     
    
    function getBusinessCategory() {
        return $this->businessCategory;
    }

    function getDescription() {
        return $this->description;
    }

    function getMember() {
        return $this->member;
    }

    function getO() {
        return $this->o;
    }

    function getOu() {
        return $this->ou;
    }

    function getOwner() {
        return $this->owner;
    }

    function getSeeAlso() {
        return $this->seeAlso;
    }

    function setBusinessCategory($businessCategory) {
        $this->businessCategory = $businessCategory;
    }

    function setDescription($description) {
        $this->description = $description;
    }

    function setMember($member) {
        $this->member = $member;
    }

    function setO($o) {
        $this->o = $o;
    }

    function setOu($ou) {
        $this->ou = $ou;
    }

    function setOwner($owner) {
        $this->owner = $owner;
    }

    function setSeeAlso($seeAlso) {
        $this->seeAlso = $seeAlso;
    }


}
