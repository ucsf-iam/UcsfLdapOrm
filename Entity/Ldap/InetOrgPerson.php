<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use IAM\DirectoryServicesBundle\Util\Phone;

/**
 * Standard LDAP InetOrgPerson. May be used as a Symfony user.
 * 
 * @author jgabler
 * @ObjectClass("OrganizationalPerson")
 */
class InetOrgPerson extends OrganizationalPerson
{
    public function __construct($username = null, $roles = null) 
    {
        parent::__construct();
    }
    
    /**
     * @Attribute("audio")
     */
    protected $audio;

    /**
     * @Attribute("businessCategory")
     */
    protected $businessCategory; 

    /**
     * @Attribute("carLicense")
     */
    protected $carLicense;

    /**
     * @Attribute("departmentNumber")
     * @ArrayField()
     */
    protected $departmentNumber;   
    
    /**
     * @Attribute("displayName")
     */
    protected $displayName;

    /**
     * @Attribute("employeeNumber")
     */
    protected $employeeNumber;

    /**
     * @Attribute("employeeType")
     */
    protected $employeeType;

    /**
     * @Attribute("givenName")
     */
    protected $givenName;

    /**
     * @Attribute("homePhone")
     */
    protected $homePhone;

    /**
     * @Attribute("homePostalAddress")
     */
    protected $homePostalAddress;

    /**
     * @Attribute("initials")
     */
    protected $initials;

    /**
     * @Attribute("jpegPhoto")
     */
    protected $jpegPhoto;

    /**
     * @Attribute("labeledURI")
     */
    protected $labeledURI;

    /**
     * @Attribute("mail")
     */
    protected $mail;

    /**
     * @Attribute("manager")
     * @ArrayField()
     */
    protected $manager;

    /**
     * @Attribute("mobile")
     */
    protected $mobile;

    /**
     * @Attribute("o")
     */
    protected $o;

    /**
     * @Attribute("pager")
     */
    protected $pager;

    /**
     * @Attribute("photo")
     */
    protected $photo;
    
    /**
     * @Attribute("preferredLanguage")
     */
    protected $preferredLanguage;

    /**
     * @Attribute("roomNumber")
     */
    protected $roomNumber;

    /**
     * @Attribute("secretary")
     */
    protected $secretary;

    /**
     * @Attribute("uid")
     */
    protected $uid;

    /**
     * @Attribute("userCertificate")
     */
    protected $userCertificate;
    
    /**
     * @Attribute("userPKCS12")
     */
    protected $userPKCS12;

    /**
     * @Attribute("userSMIMECertificate")
     */
    protected $userSMIMECertificate;
        
    /**
     * @Attribute("x500UniqueIdentifier")
     */
    protected $x500UniqueIdentifier;

    function getAudio() {
        return $this->audio;
    }

    function getBusinessCategory() {
        return $this->businessCategory;
    }

    function getCarLicense() {
        return $this->carLicense;
    }

    function getDepartmentNumber() {
        return $this->departmentNumber;
    }

    function getDisplayName() {
        return $this->displayName;
    }

    function getEmployeeNumber() {
        return $this->employeeNumber;
    }

    function getEmployeeType() {
        return $this->employeeType;
    }

    function getGivenName() {
        return $this->givenName;
    }

    function getHomePhone() {
        return $this->homePhone;
    }

    function getHomePostalAddress() {
        return $this->homePostalAddress;
    }

    function getInitials() {
        return $this->initials;
    }

    function getJpegPhoto() {
        return $this->jpegPhoto;
    }

    function getLabeledURI() {
        return $this->labeledURI;
    }

    function getMail() {
        return $this->mail;
    }

    function getManager() {
        return $this->manager;
    }

    function getMobile() {
        return $this->mobile;
    }

    function getO() {
        return $this->o;
    }

    function getPager() {
        return $this->pager;
    }

    function getPhoto() {
        return $this->photo;
    }

    function getPreferredLanguage() {
        return $this->preferredLanguage;
    }

    function getRoomNumber() {
        return $this->roomNumber;
    }

    function getSecretary() {
        return $this->secretary;
    }

    function getUid() {
        return $this->uid;
    }

    function getUserCertificate() {
        return $this->userCertificate;
    }

    function getUserPKCS12() {
        return $this->userPKCS12;
    }

    function getUserSMIMECertificate() {
        return $this->userSMIMECertificate;
    }

    function getX500UniqueIdentifier() {
        return $this->x500UniqueIdentifier;
    }

    function setAudio($audio) {
        $this->audio = $audio;
    }

    function setBusinessCategory($businessCategory) {
        $this->businessCategory = $businessCategory;
    }

    function setCarLicense($carLicense) {
        $this->carLicense = $carLicense;
    }

    function setDepartmentNumber($departmentNumber) {
        $this->departmentNumber = $departmentNumber;
    }

    function setDisplayName($displayName) {
        $this->displayName = $displayName;
    }

    function setEmployeeNumber($employeeNumber) {
        $this->employeeNumber = $employeeNumber;
    }

    function setEmployeeType($employeeType) {
        $this->employeeType = $employeeType;
    }

    function setGivenName($givenName) {
        $this->givenName = $givenName;
    }

    function setHomePhone($homePhone) {
        $this->homePhone = $homePhone;
    }

    function setHomePostalAddress($homePostalAddress) {
        $this->homePostalAddress = $homePostalAddress;
    }

    function setInitials($initials) {
        $this->initials = $initials;
    }

    function setJpegPhoto($jpegPhoto) {
        $this->jpegPhoto = $jpegPhoto;
    }

    function setLabeledURI($labeledURI) {
        $this->labeledURI = $labeledURI;
    }

    function setMail($mail) {
        $this->mail = $mail;
    }

    function setManager($manager) {
        $this->manager = $manager;
    }

    function setMobile($mobile) {
        $this->mobile = $mobile;
    }

    function setO($o) {
        $this->o = $o;
    }

    function setPager($pager) {
        $this->pager = $pager;
    }

    function setPhoto($photo) {
        $this->photo = $photo;
    }

    function setPreferredLanguage($preferredLanguage) {
        $this->preferredLanguage = $preferredLanguage;
    }

    function setRoomNumber($roomNumber) {
        $this->roomNumber = $roomNumber;
    }

    function setSecretary($secretary) {
        $this->secretary = $secretary;
    }

    function setUid($uid) {
        $this->uid = $uid;
    }

    function setUserCertificate($userCertificate) {
        $this->userCertificate = $userCertificate;
    }

    function setUserPKCS12($userPKCS12) {
        $this->userPKCS12 = $userPKCS12;
    }

    function setUserSMIMECertificate($userSMIMECertificate) {
        $this->userSMIMECertificate = $userSMIMECertificate;
    }

    function setX500UniqueIdentifier($x500UniqueIdentifier) {
        $this->x500UniqueIdentifier = $x500UniqueIdentifier;
    }

    
}
