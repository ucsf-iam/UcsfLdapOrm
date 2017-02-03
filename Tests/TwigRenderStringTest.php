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

class TwigRenderStringTest extends DatabaseTestCase {

    public function testTwigRenderString() {
        $twig = self::createService('twig');
        $template = $twig->createTemplate('Hello {{ name }} Smith');
        $rendered = $template->render(array('name' => 'Jane'));
        $this->assertEquals('Hello Jane Smith', $rendered);
    }
}