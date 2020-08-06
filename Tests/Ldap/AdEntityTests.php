<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 10/27/17
 * Time: 1:33 PM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ucsf\LdapOrmBundle\Entity\ActiveDirectory\User;

class AdEntityTests extends WebTestCase
{
    public function testUserAccountControlStrings() {
        $user = new User(null, null);
        $user->setUserAccountControl(User::ADS_UF_NORMAL_ACCOUNT);

        $this->assertEquals(User::ADS_UF_NORMAL_ACCOUNT_STR, $user->getUserAccountControlStrings()[User::ADS_UF_NORMAL_ACCOUNT]);
    }
}