<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 9/17/15
 * Time: 11:09 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ucsf\LdapOrmBundle\Entity\Ldap\Person;

class LdapEntityManagerTest extends WebTestCase {

    static protected $container;


    protected function setUp()
    {
        static::$container = static::bootKernel()->getContainer();
    }

    public function testRetrieve() {
        $em = static::$container->get('ucsf_ldap_orm.forumsys_entity_manager');
        $result = $em->retrieve(Person::class,[
            'filter' => '(sn=Gauss)',
            'searchDn' => 'dc=example,dc=com'
        ]);

        $this->assertCount(1, $result);
        $this->assertTrue(is_a($result[0],Person::class));
        $this->assertEquals('Carl Friedrich Gauss', $result[0]->getCn());

        $result = $em->retrieve(Person::class,[
            'filter' => '(sn=Gauss)',
            'searchDn' => 'ou=scientists, dc=example,dc=com'
        ]);

        $this->assertCount(0, $result);
    }

}