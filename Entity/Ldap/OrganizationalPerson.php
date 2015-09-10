<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use IAM\DirectoryServicesBundle\Util\Phone;

/**
 * Standard LDAP OrganizationalPerson. May be used as a Symfony user.
 * 
 * @author jgabler
 * @ObjectClass("OrganizationalPerson")
 */
class OrganizationalPerson extends Person
{
    
    public function __construct($username = null, $roles = null) 
    {
        parent::__construct();
    }
    
    /**
     * @Attribute("description")
     */
    protected $description;
    
    /**
     * @Attribute("description")
     */
    protected $destinationIndicator;   
    
    /**
     * @Attribute("facsimileTelephoneNumber")
     */
    protected $facsimileTelephoneNumber;    
    
   /**
     * @Attribute("internationaliSDNNumber")
     */
    protected $internationaliSDNNumber;

    /**
     * @Attribute("l")
     */
    protected $l;
    
    /**
     * @Attribute("ou")
     * A MUST attribute
     */
    protected $ou;
    
    /**
     * @Attribute("physicalDeliveryOfficeName")
     */
    protected $physicalDeliveryOfficeName;
    
    /**
     * @Attribute("postalAddress")
     */
    protected $postalAddress;
    
    /**
     * @Attribute("postalCode")
     */
    protected $postalCode;
    
    /**
     * @Attribute("postOfficeBox")
     */
    protected $postOfficeBox;

    /**
     * @Attribute("preferredDeliveryMethod")
     */
    protected $preferredDeliveryMethod;
    
    /**
     * @Attribute("registeredAddress")
     */
    protected $registeredAddress;

    /**
     * @Attribute("st")
     */
    protected $st;
    
    /**
     * @Attribute("street")
     */
    protected $street;

    /**
     * @Attribute("teletexTerminalIdentifier")
     */
    protected $teletexTerminalIdentifier;

    /**
     * @Attribute("telexNumber")
     */
    protected $telexNumber;
    
    /**
     * @Attribute("title")
     */
    protected $title;
    
    /**
     * @Attribute("x121Address")
     */
    protected $x121Address;    

    function getDescription() {
        return $this->description;
    }

    function getDestinationIndicator() {
        return $this->destinationIndicator;
    }

    function getFacsimileTelephoneNumber() {
        return $this->facsimileTelephoneNumber;
    }

    function getInternationaliSDNNumber() {
        return $this->internationaliSDNNumber;
    }

    function getL() {
        return $this->l;
    }

    function getOu() {
        return $this->ou;
    }

    function getPhysicalDeliveryOfficeName() {
        return $this->physicalDeliveryOfficeName;
    }

    function getPostalAddress() {
        return $this->postalAddress;
    }

    function getPostalCode() {
        return $this->postalCode;
    }

    function getPostOfficeBox() {
        return $this->postOfficeBox;
    }

    function getPreferredDeliveryMethod() {
        return $this->preferredDeliveryMethod;
    }

    function getRegisteredAddress() {
        return $this->registeredAddress;
    }

    function getSt() {
        return $this->st;
    }

    function getStreet() {
        return $this->street;
    }
 
    function getTeletexTerminalIdentifier() {
        return $this->teletexTerminalIdentifier;
    }

    function getTelexNumber() {
        return $this->telexNumber;
    }

    function getTitle() {
        return $this->title;
    }

    function getX121Address() {
        return $this->x121Address;
    }

    function setDescription($description) {
        $this->description = $description;
    }

    function setDestinationIndicator($destinationIndicator) {
        $this->destinationIndicator = $destinationIndicator;
    }

    function setFacsimileTelephoneNumber($facsimileTelephoneNumber) {
        $this->facsimileTelephoneNumber = $facsimileTelephoneNumber;
    }

    function setInternationaliSDNNumber($internationaliSDNNumber) {
        $this->internationaliSDNNumber = $internationaliSDNNumber;
    }

    function setL($l) {
        $this->l = $l;
    }

    function setOu($ou) {
        $this->ou = $ou;
    }

    function setPhysicalDeliveryOfficeName($physicalDeliveryOfficeName) {
        $this->physicalDeliveryOfficeName = $physicalDeliveryOfficeName;
    }

    function setPostalAddress($postalAddress) {
        $this->postalAddress = $postalAddress;
    }

    function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    function setPostOfficeBox($postOfficeBox) {
        $this->postOfficeBox = $postOfficeBox;
    }

    function setPreferredDeliveryMethod($preferredDeliveryMethod) {
        $this->preferredDeliveryMethod = $preferredDeliveryMethod;
    }

    function setRegisteredAddress($registeredAddress) {
        $this->registeredAddress = $registeredAddress;
    }

    function setSt($st) {
        $this->st = $st;
    }

    function setStreet($street) {
        $this->street = $street;
    }

    function setTeletexTerminalIdentifier($teletexTerminalIdentifier) {
        $this->teletexTerminalIdentifier = $teletexTerminalIdentifier;
    }

    function setTelexNumber($telexNumber) {
        $this->telexNumber = $telexNumber;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setX121Address($x121Address) {
        $this->x121Address = $x121Address;
    }


}
