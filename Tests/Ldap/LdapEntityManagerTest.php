<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/17/15
 * Time: 11:09 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;

use Ucsf\LdapOrmBundle\Ldap\Converter;
use Ucsf\LdapOrmBundle\Tests\DatabaseTestCase;

class LdapEntityManagerTest extends DatabaseTestCase {

    public function testAdDateConversion() {
        $adTimestamp = '130898490540000000.0Z';
        $decorator = Converter::fromAdDateTime($adTimestamp);
        $convertedAdTimestamp = Converter::toAdDateTime($decorator);

        $this->assertEquals($adTimestamp, $convertedAdTimestamp);
    }


    public function testGroupRemoveAndAdd() {
        $groupDn = 'CN=DualAuthUsersNet,OU=DualAuth,DC=net,DC=ucsf,DC=edu';
        $memberDn = 'CN=Madrigal\, Melchor,OU=UCSF,DC=Campus,DC=net,DC=ucsf,DC=edu';

        $domainEm = self::createService('domain_entity_manager')->getEntityManagerByDomain(\IAM\DirectoryServicesBundle\Util\AD::DOMAIN_NET);
        $domainEm->groupRemove($groupDn, $memberDn); // Don't care about result, just prepping for the add
        $result = $domainEm->groupAdd($groupDn, $memberDn);
        $this->assertTrue($result);
        $domainEm->groupRemove($groupDn, $memberDn);
        $this->assertTrue($result);
    }

}