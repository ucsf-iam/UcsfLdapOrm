<?php
namespace Ucsf\LdapOrmBundle\Entity\ActiveDirectory;

use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Annotation\Ldap\UniqueIdentifier;
use Ucsf\LdapOrmBundle\Annotation\Ldap\Attribute;
use Ucsf\LdapOrmBundle\Entity\Ldap\LdapEntity;

/**
 *
 * @author jgabler
 *
 * @ObjectClass("msDS-PasswordSettings")
 * @UniqueIdentifier("distinguishedName")
 */
class PasswordSettings extends LdapEntity
{
    const objectClass = 'msDS-PasswordSettings';

    /**
     * @Attribute("instanceType")
     */
    public $instanceType;


    /**
     * @Attribute("msDS-LockoutDuration")
     */
    public $lockoutDuration;


    /**
     * @Attribute("msDS-LockoutObservationWindow")
     */
    public $lockoutObservationWindow;


    /**
     * @Attribute("msDS-LockoutThreshold")
     */
    public $lockoutThreshold;

    /**
     * @Attribute("msDS-MaximumPasswordAge")
     */
    public $maximumPasswordAge;

    /**
     * @Attribute("msDS-MinimumPasswordAge")
     */
    public $minimumPasswordAge;

    /**
     * @Attribute("msDS-MinimumPasswordLength")
     */
    public $minimumPasswordLength;

    /**
     * @Attribute("msDS-PasswordComplexityEnabled")
     */
    public $passwordComplexityEnabled;

    /**
     * @Attribute("msDS-PasswordHistoryLength")
     */
    public $passwordHistoryLength;

    /**
     * @Attribute("msDS-PasswordReversibleEncryptionEnabled")
     */
    public $passwordReversibleEncryptionEnabled;


    /**
     * @Attribute("msDS-PasswordSettingsPrecedence")
     */
    public $passwordSettingsPrecedence;


    /**
     * @Attribute("dsCorePropagationDta")
     */
    public $corePropagationDta;


    /**
     * @Attribute("msDS-PSOAppliesTo")
     */
    public $psoAppliesTo;


    /**
     * @Attribute("name")
     */
    public $name;

    /**
     * @return mixed
     */
    public function getInstanceType()
    {
        return $this->instanceType;
    }

    /**
     * @param mixed $instanceType
     */
    public function setInstanceType($instanceType)
    {
        $this->instanceType = $instanceType;
    }

    /**
     * @return mixed
     */
    public function getLockoutDuration()
    {
        return $this->lockoutDuration;
    }

    /**
     * @param mixed $lockoutDuration
     */
    public function setLockoutDuration($lockoutDuration)
    {
        $this->lockoutDuration = $lockoutDuration;
    }

    /**
     * @return mixed
     */
    public function getLockoutObservationWindow()
    {
        return $this->lockoutObservationWindow;
    }

    /**
     * @param mixed $lockoutObservationWindow
     */
    public function setLockoutObservationWindow($lockoutObservationWindow)
    {
        $this->lockoutObservationWindow = $lockoutObservationWindow;
    }

    /**
     * @return mixed
     */
    public function getLockoutThreshold()
    {
        return $this->lockoutThreshold;
    }

    /**
     * @param mixed $lockoutThreshold
     */
    public function setLockoutThreshold($lockoutThreshold)
    {
        $this->lockoutThreshold = $lockoutThreshold;
    }

    /**
     * @return mixed
     */
    public function getMaximumPasswordAge()
    {
        return $this->maximumPasswordAge;
    }

    /**
     * @param mixed $maximumPasswordAge
     */
    public function setMaximumPasswordAge($maximumPasswordAge)
    {
        $this->maximumPasswordAge = $maximumPasswordAge;
    }

    /**
     * @return mixed
     */
    public function getMinimumPasswordAge()
    {
        return $this->minimumPasswordAge;
    }

    /**
     * @param mixed $minimumPasswordAge
     */
    public function setMinimumPasswordAge($minimumPasswordAge)
    {
        $this->minimumPasswordAge = $minimumPasswordAge;
    }

    /**
     * @return mixed
     */
    public function getMinimumPasswordLength()
    {
        return $this->minimumPasswordLength;
    }

    /**
     * @param mixed $minimumPasswordLength
     */
    public function setMinimumPasswordLength($minimumPasswordLength)
    {
        $this->minimumPasswordLength = $minimumPasswordLength;
    }

    /**
     * @return mixed
     */
    public function getPasswordComplexityEnabled()
    {
        return $this->passwordComplexityEnabled;
    }

    /**
     * @param mixed $passwordComplexityEnabled
     */
    public function setPasswordComplexityEnabled($passwordComplexityEnabled)
    {
        $this->passwordComplexityEnabled = $passwordComplexityEnabled;
    }

    /**
     * @return mixed
     */
    public function getPasswordHistoryLength()
    {
        return $this->passwordHistoryLength;
    }

    /**
     * @param mixed $passwordHistoryLength
     */
    public function setPasswordHistoryLength($passwordHistoryLength)
    {
        $this->passwordHistoryLength = $passwordHistoryLength;
    }

    /**
     * @return mixed
     */
    public function getPasswordReversibleEncryptionEnabled()
    {
        return $this->passwordReversibleEncryptionEnabled;
    }

    /**
     * @param mixed $passwordReversibleEncryptionEnabled
     */
    public function setPasswordReversibleEncryptionEnabled($passwordReversibleEncryptionEnabled)
    {
        $this->passwordReversibleEncryptionEnabled = $passwordReversibleEncryptionEnabled;
    }

    /**
     * @return mixed
     */
    public function getPasswordSettingsPrecedence()
    {
        return $this->passwordSettingsPrecedence;
    }

    /**
     * @param mixed $passwordSettingsPrecedence
     */
    public function setPasswordSettingsPrecedence($passwordSettingsPrecedence)
    {
        $this->passwordSettingsPrecedence = $passwordSettingsPrecedence;
    }

    /**
     * @return mixed
     */
    public function getCorePropagationDta()
    {
        return $this->corePropagationDta;
    }

    /**
     * @param mixed $corePropagationDta
     */
    public function setCorePropagationDta($corePropagationDta)
    {
        $this->corePropagationDta = $corePropagationDta;
    }

    /**
     * @return mixed
     */
    public function getPsoAppliesTo()
    {
        return $this->psoAppliesTo;
    }

    /**
     * @param mixed $psoAppliesTo
     */
    public function setPsoAppliesTo($psoAppliesTo)
    {
        $this->psoAppliesTo = $psoAppliesTo;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


}


