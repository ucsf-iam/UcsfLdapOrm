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
 * @ObjectClass("domain")
 */
class Domain extends LdapEntity
{
    const objectClass = 'domain';

    /**
     * @Attribute("maxPwdAge")
     */
    public $maxPwdAge;

    /**
     * @Attribute("minPwdAge")
     */
    public $minPwdAge;

    /**
     * @Attribute("minPwdLength")
     */
    public $minPwdLength;

    /**
     * @Attribute("lockoutDuration")
     */
    public $lockoutDuration;

    /**
     * @return mixed
     */
    public function getMaxPwdAge()
    {
        return $this->maxPwdAge;
    }

    /**
     * @param mixed $maxPwdAge
     */
    public function setMaxPwdAge($maxPwdAge)
    {
        $this->maxPwdAge = $maxPwdAge;
    }

    /**
     * @return mixed
     */
    public function getMinPwdAge()
    {
        return $this->minPwdAge;
    }

    /**
     * @param mixed $minPwdAge
     */
    public function setMinPwdAge($minPwdAge)
    {
        $this->minPwdAge = $minPwdAge;
    }

    /**
     * @return mixed
     */
    public function getMinPwdLength()
    {
        return $this->minPwdLength;
    }

    /**
     * @param mixed $minPwdLength
     */
    public function setMinPwdLength($minPwdLength)
    {
        $this->minPwdLength = $minPwdLength;
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
}


