<?php
namespace Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception;
use Ucsf\LdapOrmBundle\Doctrine\DBAL\Platforms\LDAPPlatform;

/**
 * Class AbstractLDAPDriver
 * @package Ucsf\LdapOrmBundle\Doctrine\DBAL\Driver
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
abstract class AbstractLDAPDriver implements Driver, Driver\ExceptionConverterDriver
{
    /**
     * Gets the SchemaManager that can be used to inspect and change the underlying
     * database schema of the platform this driver connects to.
     *
     * @param \Doctrine\DBAL\Connection $conn
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchemaManager(Connection $conn)
    {
        // TODO: Implement getSchemaManager() method.
    }

    /**
     * Gets the DatabasePlatform instance that provides all the metadata about
     * the platform this driver connects to.
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform The database platform.
     */
    public function getDatabasePlatform()
    {
        return new LDAPPlatform();
    }

    /**
     * Gets the name of the database connected to for this driver.
     *
     * @param \Doctrine\DBAL\Connection $conn
     *
     * @return string The name of the database.
     */
    public function getDatabase(Connection $conn)
    {
        // TODO: Implement getDatabase() method.
    }

    /**
     * Converts a given DBAL driver exception into a standardized DBAL driver exception.
     *
     * It evaluates the vendor specific error code and SQLSTATE and transforms
     * it into a unified {@link Doctrine\DBAL\Exception\DriverException} subclass.
     *
     * @param string $message The DBAL exception message to use.
     * @param \Doctrine\DBAL\Driver\DriverException $exception The DBAL driver exception to convert.
     *
     * @return \Doctrine\DBAL\Exception\DriverException An instance of one of the DriverException subclasses.
     */
    public function convertException($message, Driver\DriverException $exception)
    {
        // TODO: Implement convertException() method.
    }


}
