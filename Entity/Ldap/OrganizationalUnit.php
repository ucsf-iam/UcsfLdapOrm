<?php
namespace Ucsf\LdapOrmBundle\Entity\Ldap;


use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;


/**
 * Class to represent a person within EDS ou=people,dc=ucsf,dc=edu
 *
 * @author jgabler
 * @ObjectClass("organizationalUnit")
 */
class OrganizationalUnit extends LdapEntity {
    /**
     * @Attribute("businessCategory")
     */
    protected $businessCategory;
    
    /**
     * @Attribute("description")
     */
    protected $description;
    
    /**
     * @Attribute("destinationIndicator")
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
     * @Attribute("searchGuide")
     */
    protected $searchGuide;

    /**
     * @Attribute("seeAlso")
     */
    protected $seeAlso;
    
    /**
     * @Attribute("ou")
     * A MUST attribute
     */
    protected $ou;

    /**
     * @Attribute("st")
     */
    protected $st;
    
    /**
     * @Attribute("street")
     */
    protected $street;

    /**
     * @Attribute("telephoneNumber")
     */
    protected $telephoneNumber;

    /**
     * @Attribute("teletexTerminalIdentifier")
     */
    protected $teletexTerminalIdentifier;

    /**
     * @Attribute("telexNumber")
     */
    protected $telexNumber;
    
    /**
     * @Attribute("userPassword")
     */
    protected $userPassword;
    
    /**
     * @Attribute("x121Address")
     */
    protected $x121Address;    

    function getBusinessCategory() {
        return $this->businessCategory;
    }

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

    function getSearchGuide() {
        return $this->searchGuide;
    }

    function getSeeAlso() {
        return $this->seeAlso;
    }

    function getOu() {
        return $this->ou;
    }

    function getSt() {
        return $this->st;
    }

    function getStreet() {
        return $this->street;
    }

    function getTelephoneNumber() {
        return $this->telephoneNumber;
    }

    function getTeletexTerminalIdentifier() {
        return $this->teletexTerminalIdentifier;
    }

    function getTelexNumber() {
        return $this->telexNumber;
    }

    function getUserPassword() {
        return $this->userPassword;
    }

    function getX121Address() {
        return $this->x121Address;
    }

    function setBusinessCategory($businessCategory) {
        $this->businessCategory = $businessCategory;
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

    function setSearchGuide($searchGuide) {
        $this->searchGuide = $searchGuide;
    }

    function setSeeAlso($seeAlso) {
        $this->seeAlso = $seeAlso;
    }

    function setOu($ou) {
        $this->ou = $ou;
    }

    function setSt($st) {
        $this->st = $st;
    }

    function setStreet($street) {
        $this->street = $street;
    }

    function setTelephoneNumber($telephoneNumber) {
        $this->telephoneNumber = $telephoneNumber;
    }

    function setTeletexTerminalIdentifier($teletexTerminalIdentifier) {
        $this->teletexTerminalIdentifier = $teletexTerminalIdentifier;
    }

    function setTelexNumber($telexNumber) {
        $this->telexNumber = $telexNumber;
    }

    function setUserPassword($userPassword) {
        $this->userPassword = $userPassword;
    }

    function setX121Address($x121Address) {
        $this->x121Address = $x121Address;
    }


}