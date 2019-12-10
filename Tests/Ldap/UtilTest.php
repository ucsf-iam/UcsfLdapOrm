<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 8/5/17
 * Time: 10:36 AM
 */

namespace Ucsf\LdapOrmBundle\Tests\Ldap;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Ucsf\LdapOrmBundle\Ldap\Util;


class UtilTest extends WebTestCase
{

    // Generated from https://www.epochconverter.com/ldap
    const AD_TEST_TIME_GMT = 'July 25, 2017 2:48:05.480000 AM';
    const AD_TEST_TIME_PDT = 'July 24, 2017 7:48:05.480000 PM';
    const AD_TEST_TIME_FORMAT = 'F j, Y g:i:s.u A';
//    const AD_TEST_TIMESTAMP = '131454244854800000';
    const AD_TEST_TIMESTAMP = '131453992854800000';
//    const AD_TEST_UNIXEPOCH = '1500950885';
    const AD_TEST_UNIXEPOCH = '1500925685';

    const LDAP_TEST_TIME = 'July 25, 2017 2:48:05 AM'; // GMT
    const LDAP_TEST_TIME_FORMAT = 'F j, Y g:i:s A';
    const LDAP_TEST_TIMESTAMP = '20170725024805Z';


    public function testLdapDateToDatetime() {
        $ldapDateTime = Util::ldapDateToDatetime(self::LDAP_TEST_TIMESTAMP);
        $this->assertEquals(self::LDAP_TEST_TIME, $ldapDateTime->format(self::LDAP_TEST_TIME_FORMAT));
    }


    public function testDatetimeToLdapDate() {
        $ldapDate = Util::datetimeToLdapDate(\DateTime::createFromFormat(self::LDAP_TEST_TIME_FORMAT, self::LDAP_TEST_TIME));
        $this->assertEquals(self::LDAP_TEST_TIMESTAMP, $ldapDate);
    }


    public function testAdDateToDatetime() {
        $adDateTime = Util::adDateToDatetime(self::AD_TEST_TIMESTAMP);
        $this->assertEquals(self::AD_TEST_TIME_PDT, $adDateTime->format(self::AD_TEST_TIME_FORMAT));
        $this->assertEquals(self::AD_TEST_UNIXEPOCH, $adDateTime->format('U'));
    }
    

    public function testDatetimeToAdDate() {
        $adDate = Util::datetimeToAdDate(\DateTime::createFromFormat(self::AD_TEST_TIME_FORMAT, self::AD_TEST_TIME_PDT));
        $this->assertEquals(self::AD_TEST_TIMESTAMP, $adDate);
    }

}