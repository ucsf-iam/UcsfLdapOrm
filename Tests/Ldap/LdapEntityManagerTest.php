<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/17/15
 * Time: 11:09 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;


use Ucsf\LdapOrmBundle\Entity\Ldap\OrganizationalPerson;
use Ucsf\LdapOrmBundle\Tests\DatabaseTestCase;

class LdapEntityManagerTest extends DatabaseTestCase {

    public function testPaging()
    {
        $em = self::createService('ucsf_ldap_orm.entity_manager');
        $ucsfldaporm_test = static::$kernel->getContainer()->getParameter('ucsfldaporm_test');
        $this->assertNotNull($ucsfldaporm_test['entity_class']);
        $result = $em->retrieve($ucsfldaporm_test['entity_class'], array(
            'filter' => array('givenName' => 'John'),
        ));
        $resultCount = count($result);
        $this->assertNotEquals(0, $resultCount);
        $pagedResultCount = 0;
        do {
            $result = $em->retrieve($ucsfldaporm_test['entity_class'], array(
                'filter' => array('givenName' => 'John'),
                'pageSize' => 100,
            ));
            $pagedResultCount += count($result);
        } while ($em->pageHasMore());
        $this->assertEquals($resultCount, $pagedResultCount);
    }
}