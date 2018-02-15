<?php
namespace Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver\LDAP;

use Ucsf\LdapOrmBundle\Util;
use IteratorAggregate;
use Doctrine\DBAL\Driver\Statement;
use Traversable;

/**
 * Class LDAPStatement
 * @package Ucsf\LdapOrmBundle\DBAL\Driver\LDAP
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
class LDAPStatement implements \IteratorAggregate, Statement
{

    protected $client;
    protected $method;
    protected $uri;
    protected $json;
    protected $objects = array();

    public function __construct()
    {
        $this->client = $client;
        $this->method = $method;
        $this->uri = $uri;
    }


    /**
     * Execute the LDAP method at the given URI.
     *
     * @param array|null $params An array of values with as many elements as there are
     *                           variables in the URI
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    function execute($variables = array())
    {
        $renderedUri = Util::twigRender($this->uri, $variables);
        $response = null;
        try {
            $response = $this->client->request($this->method, $renderedUri);
            $this->objects = json_decode($response->getBody());
        } catch (\Exception $e) {
            $this->currentErrorCode = 1;
            $this->currentErrorInfo = $e->getMessage();
            throw $e;
        }
        return TRUE;
    }


    /**
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $data = $this->fetchAll();

        return new \ArrayIterator($data);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean TRUE on success or FALSE on failure.
     */
    public function closeCursor()
    {
        // TODO: Implement closeCursor() method.
    }

    /**
     * Returns the number of columns in the result set
     *
     * @return integer The number of columns in the result set represented
     *                 by the PDOStatement object. If there is no result set,
     *                 this method should return 0.
     */
    public function columnCount()
    {
        // TODO: Implement columnCount() method.
    }

    /**
     * Sets the fetch mode to use while iterating this statement.
     *
     * @param integer $fetchMode The fetch mode must be one of the PDO::FETCH_* constants.
     * @param mixed $arg2
     * @param mixed $arg3
     *
     * @return boolean
     *
     * @see PDO::FETCH_* constants.
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * Returns the next row of a result set.
     *
     * @param integer|null $fetchMode Controls how the next row will be returned to the caller.
     *                                The value must be one of the PDO::FETCH_* constants,
     *                                defaulting to PDO::FETCH_BOTH.
     *
     * @return mixed The return value of this method on success depends on the fetch mode. In all cases, FALSE is
     *               returned on failure.
     *
     * @see PDO::FETCH_* constants.
     */
    public function fetch($fetchMode = null)
    {
        // TODO: Implement fetch() method.
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param integer|null $fetchMode Controls how the next row will be returned to the caller.
     *                                The value must be one of the PDO::FETCH_* constants,
     *                                defaulting to PDO::FETCH_BOTH.
     *
     * @return array
     *
     * @see PDO::FETCH_* constants.
     */
    public function fetchAll($fetchMode = null)
    {
        return $this->objects;
    }

    /**
     * Returns a single column from the next row of a result set or FALSE if there are no more rows.
     *
     * @param integer $columnIndex 0-indexed number of the column you wish to retrieve from the row.
     *                             If no value is supplied, PDOStatement->fetchColumn()
     *                             fetches the first column.
     *
     * @return string|boolean A single column in the next row of a result set, or FALSE if there are no more rows.
     */
    public function fetchColumn($columnIndex = 0)
    {
        // TODO: Implement fetchColumn() method.
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * Binds a value to a corresponding named variable within a LDAP URI.
     * @param variable The name of the variable to bind to.
     * @param value The value of the variable to bind.
     * @return boolean TRUE on success or FALSE on failure.
     */
    function bindValue($variable, $value, $type = null)
    {
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    function bindParam($column, &$variable, $type = null, $length = null)
    {
        throw new \Exception(__METHOD__. '() is not yet implemented.');
    }

    /**
     * Fetches the SQLSTATE associated with the last operation on the statement handle.
     *
     * @see Doctrine_Adapter_Interface::errorCode()
     *
     * @return string The error code string.
     */
    function errorCode()
    {
        return $this->currentErrorCode;
    }

    /**
     * Fetches extended error information associated with the last operation on the statement handle.
     *
     * @see Doctrine_Adapter_Interface::errorInfo()
     *
     * @return array The error info array.
     */
    function errorInfo()
    {
        return $this->currentErrorInfo;
    }


    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement
     * executed by the corresponding object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement,
     * some databases may return the number of rows returned by that statement. However,
     * this behaviour is not guaranteed for all databases and should not be
     * relied on for portable applications.
     *
     * @return integer The number of rows.
     */
    function rowCount()
    {
        return count($this->objects);
    }

}
