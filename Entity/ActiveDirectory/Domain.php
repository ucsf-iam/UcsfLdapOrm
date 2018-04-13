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
     * @Attribute("lockoutDuration")
     */
    public $lockoutDuration;

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


