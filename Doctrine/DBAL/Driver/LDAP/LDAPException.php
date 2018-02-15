<?php

namespace Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP;

use Doctrine\DBAL\Driver\AbstractDriverException;

/**
 * Class LDAPException
 * @package Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class LDAPException extends AbstractDriverException
{
    /**
     * @param array $error
     *
     * @return \Doctrine\DBAL\Driver\LDAP\LDAPException
     */
    public static function fromErrorInfo($error)
    {
        return new self($error['message'], null, $error['code']);
    }
}
