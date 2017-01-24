<?php

namespace Ucsf\LdapOrmBundle\Entity\LdapOrm;

use Ucsf\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Ucsf\LdapOrmBundle\Entity\ActiveDirectory\User;

/**
 * Class DomainUser
 *
 * This class provides utilities for creating AD domain-specific users.  LdapEntityManager::retrieve() will
 * attempt to put entries into domain-specific entities depending upon the domain divined from the entries
 * distinguishedName.  See getDomainFromDn() below to see how this is done.
 *
 * @ObjectClass("user")
 */
class DomainUser extends User
{

    /**
     * @return mixed
     */
    public function getDomain() {
        if (!isset($this->domain)) {
            $this->setDomain();
        }
        return $this->domain;
    }


    /**
     * @param null $domain
     */
    protected function setDomain($domain = null) {
        if ($domain) {
            $this->domain = $domain;
        } else {
            if (!isset($this->domain) && isset($this->dn)) {
                $this->domain = $this->getDomainFromDn($this->dn);
            }
        }
    }


    /**
     * Convert an AD entity's DN into an AD domain name
     * @param $dn
     * @return string
     */
    public static function getDomainFromDn($dn) {
        $dc = preg_split('/,dc=/i', $dn);
        array_shift($dc);
        return strtolower(implode('.', $dc));
    }
}