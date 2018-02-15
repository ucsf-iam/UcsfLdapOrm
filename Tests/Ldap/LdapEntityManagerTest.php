<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/17/15
 * Time: 11:09 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class LdapEntityManagerTest extends WebTestCase {

    private $ldapEntityManager;

    public function setUp() {
        $container = static::bootKernel()->getContainer();
        $this->ldapEntityManager = $container->get('ucsf_ldap_orm_entity_manager');
    }

    public function testFind() {
        
        $this->ldapEntityManager->find()

    }
}