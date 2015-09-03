<?php

namespace Ucsf\LdapOrmBundle\Entity\Ldap;

use Doctrine\ORM\Mapping as ORM;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Must;
use IAM\DirectoryServicesBundle\Util;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;


/**
 * Standard LDAP Person entry with implementations of Symfony user
 * security modules.
 * 
 * @author jgabler
 * @ObjectClass("Person")
 */
class Person extends LdapEntity implements UserInterface, EquatableInterface
{
    
    /**
     * @Attribute("sn")
     * @Must()
     */
    protected $sn;

    /**
     * @Attribute("description")
     */
    protected $description;
    
    /**
     * @Attribute("seeAlso")
     */
    protected $seeAlso;

    /**
     * @Attribute("telephoneNumber")
     */
    protected $telephoneNumber;
    
    /**
     * @Attribute("userPassword")
     * @ArrayField()
     */
    protected $userPassword;
    
    /* **************************************************************************************************************
     * Custom LDAP field functions and helpers
     * **************************************************************************************************************/    

    public function getTelephoneNumberView() {
        return empty($this->telephoneNumber) ? $this->telephoneNumber : Util::telephoneNumberView($this->telephoneNumber);
    }
    
    public function getTelephoneNumberDial() {
        return empty($this->telephoneNumber) ? $this->telephoneNumber : Util::telephoneNumberDial($this->telephoneNumber);
    }    
    
    public function getTelephoneNumberEds() {
        return empty($this->telephoneNumber) ? $this->telephoneNumber : Util::telephoneNumberEds($this->telephoneNumber);
    }
    
    public function getTelephoneNumberCls() {
        return empty($this->telephoneNumber) ? $this->telephoneNumber : Util::telephoneNumberCls($this->telephoneNumber);
    }    

    /* **************************************************************************************************************
     * Standard LDAP field functions
     * **************************************************************************************************************/        
   
    function getSn() {
        return $this->sn;
    }

    function getDescription() {
        return $this->description;
    }

    function getSeeAlso() {
        return $this->seeAlso;
    }

    function getTelephoneNumber() {
        return $this->telephoneNumber;
    }

    function getUserPassword($type = null) {
            if (empty($this->userPassword) || is_scalar($this->userPassword) || $type == null) {
            return $this->userPassword;
        }

        foreach($this->userPassword as $passwd) {
            $needle = '{'.$type.'}';
            if (strpos($passwd, $needle) === 0) {
                return substr($passwd, strlen($needle));
            }
        }

        throw new UnknownUserPasswordType('Unknown password type requested: "'.$type.'"');
    }

    function setSn($sn) {
        $this->sn = $sn;
    }

    function setDescription($description) {
        $this->description = $description;
    }

    function setSeeAlso($seeAlso) {
        $this->seeAlso = $seeAlso;
    }

    function setTelephoneNumber($telephoneNumber) {
        $this->telephoneNumber = $telephoneNumber;
    }

    function setUserPassword($userPassword = null) {
        if (empty($userPassword)) {
            unset($this->userPassword);
        } else {
            $this->userPassword = $userPassword;
        }
    }
    
    /* **************************************************************************************************************
     * Symfony UserInterface & EquatableInterface Implementations
     * **************************************************************************************************************/
        
    protected $username;
    protected $password;
    protected $salt;
    protected $roles;

    public function __construct($username = null, $roles = null) 
    {
        parent::__construct();
        $this->roles = empty($roles) ? array() : $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function hasRoles($role) {
        return in_array($role, $this->roles);
    }

    public function setRoles($roles) {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    public function addRole($role) {
        $this->roles[] = $role;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
//        if (!$user instanceof EdsUser) {
        if (!$user instanceof Person) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
    
}

class UnknownUserPasswordType extends \Exception {}
