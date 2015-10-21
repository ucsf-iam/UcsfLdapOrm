<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/17/15
 * Time: 11:09 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;


use Ucsf\LdapOrmBundle\Entity\Ldap\OrganizationalPerson;
use Ucsf\LdapOrmBundle\Ldap\Converter;
use Ucsf\LdapOrmBundle\Tests\DatabaseTestCase;

class LdapEntityManagerTest extends DatabaseTestCase {

    public function testAdDateConversion() {
        $adTimestamp = '130898490540000000.0Z';
        $decorator = Converter::fromAdDateTime($adTimestamp);
        $convertedAdTimestamp = Converter::toAdDateTime($decorator);

        $this->assertEquals($adTimestamp, $convertedAdTimestamp);
    }
}