<?php

namespace Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Ucsf\LdapOrmBundle\Util;
use Symfony\Component\Ldap\Adapter\ExtLdap\Connection as SymfonyLdapConnection;


/**
 * OCI8 implementation of the Connection interface.
 *
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class LDAPConnection implements Connection, ServerInfoAwareConnection
{
    const VERSION = '1.0';

    protected $currentErrorInfo;
    protected $currentErrorCode;

    /**
     * @var Client
     * The LDAP client
     */
    protected $symfonyLdapConnection;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    public function __construct($uri, $bindDn, $password)
    {
        $this->twig = new \Twig_Environment(new \Twig_Loader_Array());
        $this->symfonyLdapConnection = new SymfonyLdapConnection(['connection_string' => $uri]);
        $this->symfonyLdapConnection->bind($bindDn, $password);
    }


    /**
     * Prepares a statement for execution and returns a Statement object.
     *
     * @param string $prepareString
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws LDAPException
     */
    function prepare($prepareString)
    {
        return new LDAPStatement($this->symfonyLdapConnection, $prepareString, $this);
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @param uri URI path related to $base_uri
     * @return \Doctrine\DBAL\Driver\Statement
     * @throws LDAPException
     */
    function query()
    {
        $args = func_get_args();

        // First argument, $baseDn
        if (empty($args[0])) {
            throw new LDAPException('Cannot call query($baseDn, $filter) without a LDAP base DN as the first argument.');
        }
        $baseDn = $args[0];

        // Second argument, $filter
        if (empty($args[1])) {
            throw new LDAPException('Cannot call query($baseDn, $filter) without a LDAP filter as the second argument.');
        }
        $filter = $args[1];

        $statement = $this->prepare($baseDn, $filter);
        $statement->execute();

        return $statement;
    }

    /**
     * Quotes a string for use in a query.  This is implemented because Connect
     * requires it. For LDAP, good JSON is expected and will not be validated or
     * automatically escaped.
     *
     * @param string $input
     * @param integer $type
     *
     * @return string
     */
    function quote($input, $type = \PDO::PARAM_STR)
    {
        return $input;
    }

    /**
     * Executes a LDAP call and return the number of affected rows.
     *
     * @param string $statement
     *
     * @return integer
     */
    function exec($statement)
    {
        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name
     *
     * @return string
     */
    function lastInsertId($name = null)
    {
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * LDAP is always autocommit. Return FALSE.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function beginTransaction()
    {
        return FALSE;
    }

    /**
     * There is no commit with LDAP.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function commit()
    {
        return FALSE;
    }

    /**
     * There is no rollback with LDAP. Just return TRUE.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function rollBack()
    {
        return FALSE;
    }

    /**
     * Returns the error code associated with the last operation on the database handle.
     *
     * @return string|null The error code, or null if no operation has been run on the database handle.
     */
    function errorCode()
    {
        return $this->currentErrorCode;
    }

    /**
     * Returns extended error information associated with the last operation on the database handle.
     *
     * @return array
     */
    function errorInfo()
    {
        return $this->currentErrorInfo;
    }

    /**
     * Returns the version number of the database server connected to.
     *
     * @return string
     */
    public function getServerVersion()
    {
        return self::VERSION;
    }

    /**
     * Checks whether a query is required to retrieve the database server version.
     *
     * @return boolean True if a query is required to retrieve the database server version, false otherwise.
     */
    public function requiresQueryForServerVersion()
    {
        return false;
    }

}
