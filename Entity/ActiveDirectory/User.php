<?php

namespace Ucsf\LdapOrmBundle\Entity\ActiveDirectory;

use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;
use IAM\DirectoryServicesBundle\Util\Phone;
use Ucsf\LdapOrmBundle\Annotation\Ldap\UniqueIdentifier;
use Ucsf\LdapOrmBundle\Entity\Ldap\OrganizationalPerson;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Operational;


/**
 * 
 * @author jgabler
 *
 * @ObjectClass("user")
 * @UniqueIdentifier("distinguishedName")
 */
class User extends OrganizationalPerson {

    const SAMACCOUNTNAME_REGEX = '/^[^"\/\\\[\]:;\|=,\+\*\?<>\@]{1,20}$/';
    const MS_EXCH_RECIPIENT_TYPE_DETAILS_ENABLED = '2147483648';


    const ADS_UF_SCRIPT                                  = 1;        // 0x1
    const ADS_UF_ACCOUNTDISABLE                          = 2;        // 0x2
    const ADS_UF_HOMEDIR_REQUIRED                        = 8;        // 0x8
    const ADS_UF_LOCKOUT                                 = 16;       // 0x10
    const ADS_UF_PASSWD_NOTREQD                          = 32;       // 0x20
    const ADS_UF_PASSWD_CANT_CHANGE                      = 64;       // 0x40
    const ADS_UF_ENCRYPTED_TEXT_PASSWORD_ALLOWED         = 128;      // 0x80
    const ADS_UF_TEMP_DUPLICATE_ACCOUNT                  = 256;      // 0x100
    const ADS_UF_NORMAL_ACCOUNT                          = 512;      // 0x200
    const ADS_UF_INTERDOMAIN_TRUST_ACCOUNT               = 2048;     // 0x800
    const ADS_UF_WORKSTATION_TRUST_ACCOUNT               = 4096;     // 0x1000
    const ADS_UF_SERVER_TRUST_ACCOUNT                    = 8192;     // 0x2000
    const ADS_UF_DONT_EXPIRE_PASSWD                      = 65536;    // 0x10000
    const ADS_UF_MNS_LOGON_ACCOUNT                       = 131072;   // 0x20000
    const ADS_UF_SMARTCARD_REQUIRED                      = 262144;   // 0x40000
    const ADS_UF_TRUSTED_FOR_DELEGATION                  = 524288;   // 0x80000
    const ADS_UF_NOT_DELEGATED                           = 1048576;  // 0x100000
    const ADS_UF_USE_DES_KEY_ONLY                        = 2097152;  // 0x200000
    const ADS_UF_DONT_REQUIRE_PREAUTH                    = 4194304;  // 0x400000
    const ADS_UF_PASSWORD_EXPIRED                        = 8388608;  // 0x800000
    const ADS_UF_TRUSTED_TO_AUTHENTICATE_FOR_DELEGATION  = 16777216; // 0x1000000

    const ADS_UF_SCRIPT_STR                                  = 'script';
    const ADS_UF_ACCOUNTDISABLE_STR                          = 'account disabled';
    const ADS_UF_HOMEDIR_REQUIRED_STR                        = 'home directory required';
    const ADS_UF_LOCKOUT_STR                                 = 'lockout';
    const ADS_UF_PASSWD_NOTREQD_STR                          = 'password not required';
    const ADS_UF_PASSWD_CANT_CHANGE_STR                      = 'password cannot change';
    const ADS_UF_ENCRYPTED_TEXT_PASSWORD_ALLOWED_STR         = 'encrypted text password allowed';
    const ADS_UF_TEMP_DUPLICATE_ACCOUNT_STR                  = 'temporary duplicate account';
    const ADS_UF_NORMAL_ACCOUNT_STR                          = 'normal account';
    const ADS_UF_INTERDOMAIN_TRUST_ACCOUNT_STR               = 'interdomain trust account';
    const ADS_UF_WORKSTATION_TRUST_ACCOUNT_STR               = 'workstation trust account';
    const ADS_UF_SERVER_TRUST_ACCOUNT_STR                    = 'server trust account';
    const ADS_UF_DONT_EXPIRE_PASSWD_STR                      = 'password does not expire';
    const ADS_UF_MNS_LOGON_ACCOUNT_STR                       = 'MNS logon account';
    const ADS_UF_SMARTCARD_REQUIRED_STR                      = 'smartcard required';
    const ADS_UF_TRUSTED_FOR_DELEGATION_STR                  = 'trusted for delegation';
    const ADS_UF_NOT_DELEGATED_STR                           = 'not delegated';
    const ADS_UF_USE_DES_KEY_ONLY_STR                        = 'use DES key only';
    const ADS_UF_DONT_REQUIRE_PREAUTH_STR                    = 'pre-auth not required';
    const ADS_UF_PASSWORD_EXPIRED_STR                        = 'password not required';
    const ADS_UF_TRUSTED_TO_AUTHENTICATE_FOR_DELEGATION_STR  = 'trusted to authenticate for delegation';

    public $domain;

    public $userAccountControlDict = [];


    public function __construct($username = null, $roles = null)
    {
        parent::__construct($username, $roles);

        $this->userAccountControlDict = [
          self::ADS_UF_SCRIPT => self::ADS_UF_SCRIPT_STR,
          self::ADS_UF_ACCOUNTDISABLE => self::ADS_UF_ACCOUNTDISABLE_STR,
          self::ADS_UF_HOMEDIR_REQUIRED => self::ADS_UF_HOMEDIR_REQUIRED_STR,
          self::ADS_UF_LOCKOUT => self::ADS_UF_LOCKOUT_STR,
          self::ADS_UF_PASSWD_NOTREQD => self::ADS_UF_PASSWD_NOTREQD_STR,
          self::ADS_UF_PASSWD_CANT_CHANGE => self::ADS_UF_PASSWD_CANT_CHANGE_STR,
          self::ADS_UF_ENCRYPTED_TEXT_PASSWORD_ALLOWED => self::ADS_UF_ENCRYPTED_TEXT_PASSWORD_ALLOWED_STR,
          self::ADS_UF_TEMP_DUPLICATE_ACCOUNT => self::ADS_UF_TEMP_DUPLICATE_ACCOUNT_STR,
          self::ADS_UF_NORMAL_ACCOUNT => self::ADS_UF_NORMAL_ACCOUNT_STR,
          self::ADS_UF_INTERDOMAIN_TRUST_ACCOUNT => self::ADS_UF_INTERDOMAIN_TRUST_ACCOUNT_STR,
          self::ADS_UF_WORKSTATION_TRUST_ACCOUNT => self::ADS_UF_WORKSTATION_TRUST_ACCOUNT_STR,
          self::ADS_UF_SERVER_TRUST_ACCOUNT => self::ADS_UF_SERVER_TRUST_ACCOUNT_STR,
          self::ADS_UF_DONT_EXPIRE_PASSWD => self::ADS_UF_DONT_EXPIRE_PASSWD_STR,
          self::ADS_UF_MNS_LOGON_ACCOUNT => self::ADS_UF_MNS_LOGON_ACCOUNT_STR,
          self::ADS_UF_SMARTCARD_REQUIRED => self::ADS_UF_SMARTCARD_REQUIRED_STR,
          self::ADS_UF_TRUSTED_FOR_DELEGATION => self::ADS_UF_TRUSTED_FOR_DELEGATION_STR,
          self::ADS_UF_NOT_DELEGATED => self::ADS_UF_NOT_DELEGATED_STR,
          self::ADS_UF_USE_DES_KEY_ONLY => self::ADS_UF_USE_DES_KEY_ONLY_STR,
          self::ADS_UF_DONT_REQUIRE_PREAUTH => self::ADS_UF_DONT_REQUIRE_PREAUTH_STR,
          self::ADS_UF_PASSWORD_EXPIRED => self::ADS_UF_PASSWORD_EXPIRED_STR,
          self::ADS_UF_TRUSTED_TO_AUTHENTICATE_FOR_DELEGATION => self::ADS_UF_TRUSTED_TO_AUTHENTICATE_FOR_DELEGATION_STR,
        ];
    }


    public function getDomain() {
        if (!$this->domain) {
            $domain = null;
            if (!empty($this->dn)) {
                $rdns = explode(',dc=', strtolower($this->dn));
                array_shift($rdns);
                $this->domain = implode('.', $rdns);
            }
        }

        return $this->domain;
    }

    public function setDn($dn)
    {
        parent::setDn($dn);
        $this->getDomain(); // Now that we have the DN, prime the $this->domain convenience member variable
    }


    /**
     * Is this account mail-enabled?
     */
    public function isMailEnabled() {
        return $this->getMsExchRecipientTypeDetails() == self::MS_EXCH_RECIPIENT_TYPE_DETAILS_ENABLED;
    }


    /**
     * @Attribute("co")
     */
    public $co;

    /**
     * @Attribute("company")
     */
    public $company;

    /**
     * @Attribute("department")
     */
    public $department;

    /**
     * @Attribute("displayname")
     */
    public $displayname;

    /**
     * @Attribute("distinguishedName")
     */
    public $distinguishedName;

    /**
     * @Attribute("employeeId")
     */
    public $employeeId;

    /**
     * @Attribute("extensionAttribute10")
     */
    public $extensionAttribute10;

    /**
     * @Attribute("extensionAttribute11")
     */
    public $extensionAttribute11;

    /**
     * @Attribute("extensionAttribute9")
     */
    public $extensionAttribute9;

    /**
     * @Attribute("generationQualifier")
     */
    public $generationQualifier;

    /**
     * @Attribute("givenName")
     */
    public $givenName;

    /**
     * @Attribute("homeMDB")
     */
    public $homeMDB;

    /**
     * @Attribute("homeMTA")
     */
    public $homeMTA;

    /**
     * @Attribute("instanceType")
     */
    public $instanceType;

    /**
     * @Attribute("l")
     */
    public $l;

    /**
     * @Attribute("lastLogonTimestamp")
     */
    public $lastLogonTimestamp;

    /**
     * @Attribute("legacyExchangeDN")
     */
    public $legacyExchangeDN;

    /**
     * @Attribute("mDBUseDefaults")
     */
    public $mDBUseDefaults;

    /**
     * @Attribute("mail")
     */
    public $mail;

    /**
     * @Attribute("mailNickname")
     */
    public $mailNickname;

    /**
     * @Attribute("memberOf")
     * @ArrayField()
     */
    public $memberOf;

    /**
     * @Attribute("msExchHomeServerName")
     */
    public $msExchHomeServerName;

    /**
     * @Attribute("msExchMailboxGuid")
     */
    public $msExchMailboxGuid;

    /**
     * @Attribute("msExchMailboxSecurityDescriptor")
     */
    public $msExchMailboxSecurityDescriptor;

    /**
     * @Attribute("msExchMobileMailboxPolicyLink")
     */
    public $msExchMobileMailboxPolicyLink;

    /**
     * @Attribute("msExchObjectsDeletedThisPeriod")
     */
    public $msExchObjectsDeletedThisPeriod;

    /**
     * @Attribute("msExchPoliciesExcluded")
     */
    public $msExchPoliciesExcluded;

    /**
     * @Attribute("msExchRBACPolicyLink")
     */
    public $msExchRBACPolicyLink;

    /**
     * @Attribute("msExchRecipientDisplayType")
     */
    public $msExchRecipientDisplayType;

    /**
     * @Attribute("msExchRecipientTypeDetails")
     */
    public $msExchRecipientTypeDetails;

    /**
     * @Attribute("msExchTextMessagingState")
     */
    public $msExchTextMessagingState;

    /**
     * @Attribute("msExchUMDtmfMap")
     */
    public $msExchUMDtmfMap;

    /**
     * @Attribute("msExchUserAccountControl")
     */
    public $msExchUserAccountControl;

    /**
     * @Attribute("msExchUserCulture")
     */
    public $msExchUserCulture;

    /**
     * @Attribute("msExchVersion")
     */
    public $msExchVersion;

    /**
     * @Attribute("msExchWhenMailboxCreated")
     */
    public $msExchWhenMailboxCreated;

    /**
     * @Attribute("name")
     */
    public $name;

    /**
     * @Attribute("postalCode")
     */
    public $postalCode;

    /**
     * @Attribute("primaryGroupID")
     */
    public $primaryGroupID;

    /**
     * @Attribute("proxyAddresses")
     * @ArrayField()
     */
    public $proxyAddresses;

    /**
     * @Attribute("pwdLastSet")
     * @Operational()
     */
    public $pwdLastSet;

    /**
     * @Attribute("sAMAccountName")
     */
    public $sAMAccountName;

    /**
     * @Attribute("sAMAccountType")
     */
    public $sAMAccountType;

    /**
     * @Attribute("showInAddressBook")
     */
    public $showInAddressBook;

    /**
     * @Attribute("sn")
     */
    public $sn;

    /**
     * @Attribute("st")
     */
    public $st;

    /**
     * @Attribute("streetAddress")
     */
    public $streetAddress;

    /**
     * @Attribute("title")
     */
    public $title;

    /**
     * @Attribute("userAccountControl")
     */
    public $userAccountControl;

    /**
     * @Attribute("userPrincipalName")
     */
    public $userPrincipalName;

    /**
     * @Attribute("whenChanged")
     * @Operational()
     */
    public $whenChanged;

    /**
     * @Attribute("whenCreated")
     * @Operational()
     */
    public $whenCreated;

    /**
     * @Attribute("mobile")
     */
    public $mobile;

    /**
     * @Attribute("pager")
     */
    public $pager;

    /**
     * @Attribute("roomNumber")
     */
    public $roomNumber;

    /**
     * @Attribute("msDS-ResultantPSO")
     */
    public $msDsResultantPso;

    /**
     * @Attribute("street")
     */
    public $street;

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @Attribute("accountExpires")
     */
    public $accountExpires;

    /**
     * @param mixed $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }



    /**
     * @return mixed
     */
    public function getRoomNumber()
    {
        return $this->roomNumber;
    }

    /**
     * @param mixed $roomNumber
     */
    public function setRoomNumber($roomNumber)
    {
        $this->roomNumber = $roomNumber;
    }

    /**
     * @return mixed
     */
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * @param mixed $pager
     */
    public function setPager($pager)
    {
        $this->pager = $pager;
    }
    
    
    
    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }

    public function getDisplayname() {
        return $this->displayname;
    }

    public function getCo() {
        return $this->co;
    }

    public function getCompany() {
        return $this->company;
    }

    public function getDepartment() {
        return $this->department;
    }

    public function getDistinguishedName() {
        return $this->distinguishedName;
    }

    public function getEmployeeID() {
        return $this->employeeId;
    }

    public function getExtensionAttribute10() {
        return $this->extensionAttribute10;
    }

    public function getExtensionAttribute11() {
        return $this->extensionAttribute11;
    }

    public function getExtensionAttribute9() {
        return $this->extensionAttribute9;
    }

    public function getGenerationQualifier() {
        return $this->generationQualifier;
    }

    public function getGivenName() {
        return $this->givenName;
    }

    public function getHomeMDB() {
        return $this->homeMDB;
    }

    public function getHomeMTA() {
        return $this->homeMTA;
    }

    public function getInstanceType() {
        return $this->instanceType;
    }

    public function getL() {
        return $this->l;
    }

    public function getLastLogonTimestamp() {
        return $this->lastLogonTimestamp;
    }

    public function getLegacyExchangeDN() {
        return $this->legacyExchangeDN;
    }

    public function getMDBUseDefaults() {
        return $this->mDBUseDefaults;
    }

    public function getMail() {
        return $this->mail;
    }

    public function getMailNickname() {
        return $this->mailNickname;
    }

    public function getMemberOf() {
        return $this->memberOf;
    }

    public function getMsExchHomeServerName() {
        return $this->msExchHomeServerName;
    }

    public function getMsExchMailboxGuid() {
        return $this->msExchMailboxGuid;
    }

    public function getMsExchMailboxSecurityDescriptor() {
        return $this->msExchMailboxSecurityDescriptor;
    }

    public function getMsExchMobileMailboxPolicyLink() {
        return $this->msExchMobileMailboxPolicyLink;
    }

    public function getMsExchObjectsDeletedThisPeriod() {
        return $this->msExchObjectsDeletedThisPeriod;
    }

    public function getMsExchPoliciesExcluded() {
        return $this->msExchPoliciesExcluded;
    }

    public function getMsExchRBACPolicyLink() {
        return $this->msExchRBACPolicyLink;
    }

    public function getMsExchRecipientDisplayType() {
        return $this->msExchRecipientDisplayType;
    }

    public function getMsExchRecipientTypeDetails() {
        return $this->msExchRecipientTypeDetails;
    }

    public function getMsExchTextMessagingState() {
        return $this->msExchTextMessagingState;
    }

    public function getMsExchUMDtmfMap() {
        return $this->msExchUMDtmfMap;
    }

    public function getMsExchUserAccountControl() {
        return $this->msExchUserAccountControl;
    }

    public function getMsExchUserCulture() {
        return $this->msExchUserCulture;
    }

    public function getMsExchVersion() {
        return $this->msExchVersion;
    }

    public function getMsExchWhenMailboxCreated() {
        return $this->msExchWhenMailboxCreated;
    }

    public function getName() {
        return $this->name;
    }

    public function getPostalCode() {
        return $this->postalCode;
    }

    public function getPrimaryGroupID() {
        return $this->primaryGroupID;
    }

    public function getProxyAddresses() {
        return $this->proxyAddresses;
    }

    public function getPwdLastSet() {
        return $this->pwdLastSet;
    }

    public function getSAMAccountName() {
        return $this->sAMAccountName;
    }

    public function getSAMAccountType() {
        return $this->sAMAccountType;
    }

    public function getShowInAddressBook() {
        return $this->showInAddressBook;
    }

    public function getSn() {
        return $this->sn;
    }

    public function getSt() {
        return $this->st;
    }

    public function getStreetAddress() {
        return $this->streetAddress;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getUserAccountControl() {
        return $this->userAccountControl;
    }

    public function getUserAccountControlStrings() {
        $strings = [];
        if ($this->userAccountControl) {
            foreach ($this->userAccountControlDict as $key => $val) {
                if ($key & $this->userAccountControl) {
                    $strings[$key] = $val;
                }
            }
        }
        return $strings;
    }    

    public function getUserPrincipalName() {
        return $this->userPrincipalName;
    }

    public function getWhenChanged() {
        return $this->whenChanged;
    }

    public function getWhenCreated() {
        return $this->whenCreated;
    }

    public function setCo($co) {
        $this->co = $co;
    }

    public function setCompany($company) {
        $this->company = $company;
    }

    public function setDepartment($department) {
        $this->department = $department;
    }

    public function setDisplayName($displayname) {
        $this->displayname = $displayname;
    }

    public function setDistinguishedName($distinguishedName) {
        $this->distinguishedName = $distinguishedName;
    }

    public function setEmployeeID($employeeId) {
        $this->employeeId = $employeeId;
    }

    public function setExtensionAttribute10($extensionAttribute10) {
        $this->extensionAttribute10 = $extensionAttribute10;
    }

    public function setExtensionAttribute11($extensionAttribute11) {
        $this->extensionAttribute11 = $extensionAttribute11;
    }

    public function setExtensionAttribute9($extensionAttribute9) {
        $this->extensionAttribute9 = $extensionAttribute9;
    }

    public function setGenerationQualifier($generationQualifier) {
        $this->generationQualifier = $generationQualifier;
    }

    public function setGivenName($givenName) {
        $this->givenName = $givenName;
    }

    public function setHomeMDB($homeMDB) {
        $this->homeMDB = $homeMDB;
    }

    public function setHomeMTA($homeMTA) {
        $this->homeMTA = $homeMTA;
    }

    public function setInstanceType($instanceType) {
        $this->instanceType = $instanceType;
    }

    public function setL($l) {
        $this->l = $l;
    }

    public function setLastLogonTimestamp($lastLogonTimestamp) {
        $this->lastLogonTimestamp = $lastLogonTimestamp;
    }

    public function setLegacyExchangeDN($legacyExchangeDN) {
        $this->legacyExchangeDN = $legacyExchangeDN;
    }

    public function setMDBUseDefaults($mDBUseDefaults) {
        $this->mDBUseDefaults = $mDBUseDefaults;
    }

    public function setMail($mail) {
        $this->mail = $mail;
    }

    public function setMailNickname($mailNickname) {
        $this->mailNickname = $mailNickname;
    }

    public function setMemberOf($memberOf) {
        $this->memberOf = $memberOf;
    }

    public function setMsExchHomeServerName($msExchHomeServerName) {
        $this->msExchHomeServerName = $msExchHomeServerName;
    }

    public function setMsExchMailboxGuid($msExchMailboxGuid) {
        $this->msExchMailboxGuid = $msExchMailboxGuid;
    }

    public function setMsExchMailboxSecurityDescriptor($msExchMailboxSecurityDescriptor) {
        $this->msExchMailboxSecurityDescriptor = $msExchMailboxSecurityDescriptor;
    }

    public function setMsExchMobileMailboxPolicyLink($msExchMobileMailboxPolicyLink) {
        $this->msExchMobileMailboxPolicyLink = $msExchMobileMailboxPolicyLink;
    }

    public function setMsExchObjectsDeletedThisPeriod($msExchObjectsDeletedThisPeriod) {
        $this->msExchObjectsDeletedThisPeriod = $msExchObjectsDeletedThisPeriod;
    }

    public function setMsExchPoliciesExcluded($msExchPoliciesExcluded) {
        $this->msExchPoliciesExcluded = $msExchPoliciesExcluded;
    }

    public function setMsExchRBACPolicyLink($msExchRBACPolicyLink) {
        $this->msExchRBACPolicyLink = $msExchRBACPolicyLink;
    }

    public function setMsExchRecipientDisplayType($msExchRecipientDisplayType) {
        $this->msExchRecipientDisplayType = $msExchRecipientDisplayType;
    }

    public function setMsExchRecipientTypeDetails($msExchRecipientTypeDetails) {
        $this->msExchRecipientTypeDetails = $msExchRecipientTypeDetails;
    }

    public function setMsExchTextMessagingState($msExchTextMessagingState) {
        $this->msExchTextMessagingState = $msExchTextMessagingState;
    }

    public function setMsExchUMDtmfMap($msExchUMDtmfMap) {
        $this->msExchUMDtmfMap = $msExchUMDtmfMap;
    }

    public function setMsExchUserAccountControl($msExchUserAccountControl) {
        $this->msExchUserAccountControl = $msExchUserAccountControl;
    }

    public function setMsExchUserCulture($msExchUserCulture) {
        $this->msExchUserCulture = $msExchUserCulture;
    }

    public function setMsExchVersion($msExchVersion) {
        $this->msExchVersion = $msExchVersion;
    }

    public function setMsExchWhenMailboxCreated($msExchWhenMailboxCreated) {
        $this->msExchWhenMailboxCreated = $msExchWhenMailboxCreated;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    public function setPrimaryGroupID($primaryGroupID) {
        $this->primaryGroupID = $primaryGroupID;
    }

    public function setProxyAddresses($proxyAddresses) {
        $this->proxyAddresses = $proxyAddresses;
    }

    public function setPwdLastSet($pwdLastSet) {
        $this->pwdLastSet = $pwdLastSet;
    }

    public function setSAMAccountName($sAMAccountName) {
        $this->sAMAccountName = $sAMAccountName;
    }

    public function setSAMAccountType($sAMAccountType) {
        $this->sAMAccountType = $sAMAccountType;
    }

    public function setShowInAddressBook($showInAddressBook) {
        $this->showInAddressBook = $showInAddressBook;
    }

    public function setSn($sn) {
        $this->sn = $sn;
    }

    public function setSt($st) {
        $this->st = $st;
    }

    public function setStreetAddress($streetAddress) {
        $this->streetAddress = $streetAddress;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setUserAccountControl($userAccountControl) {
        $this->userAccountControl = $userAccountControl;
    }

    public function setUserPrincipalName($userPrincipalName) {
        $this->userPrincipalName = $userPrincipalName;
    }

    public function setWhenChanged($whenChanged) {
        $this->whenChanged = $whenChanged;
    }

    public function setWhenCreated($whenCreated) {
        $this->whenCreated = $whenCreated;
    }

    public function getMsDsResultantPso()
    {
        return $this->msDsResultantPso;
    }

    public function setMsDsResultantPso($msDsResultantPso)
    {
        $this->msDsResultantPso = $msDsResultantPso;
    }

    /**
     * @return mixed
     */
    public function getAccountExpires()
    {
        return $this->accountExpires;
    }

    /**
     * @param mixed $accountExpires
     */
    public function setAccountExpires($accountExpires)
    {
        $this->accountExpires = $accountExpires;
    }

    public function getAccountExpiresString($format = 'F j, Y h:i:s A') {
        $str = 'Unknown';
        if (isset($this->accountExpires)) {
            if (is_object($this->accountExpires)) {
                if (is_a($this->accountExpires, \DateTime::class)) {
                    $str = $this->accountExpires->format($format);
                    if (strpos($str, 'September 13, 30828') !== FALSE) {
                        $str = 'Never';
                    }
                }
            } else {
                if ($this->accountExpires == 0) {
                    $str = 'Never';
                }
            }
        }
        return $str;
    }
}
