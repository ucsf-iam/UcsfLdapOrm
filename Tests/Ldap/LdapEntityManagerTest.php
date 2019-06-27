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


    public function testGroupAdd() {
        $groupDn = 'CN=DualAuthUsersNet,OU=DualAuth,DC=net,DC=ucsf,DC=edu';
        $memberDn = 'CN=Jason2 Gabler,OU=ReEnabled Users,DC=Campus,DC=net,DC=ucsf,DC=edu';

        $domainEm = self::createService('myid_domain_entity_manager');
        $campusEm = $domainEm->getEntityManagerByDomain('net.ucsf.edu');
        $result = $campusEm->groupAdd($groupDn, $memberDn);


    }

}