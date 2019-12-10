<?php
/**
 * Created by PhpStorm.
 * User: jgabler
 * Date: 8/5/17
 * Time: 10:06 AM
 */

namespace Ucsf\LdapOrmBundle\Ldap;


use Ucsf\LdapOrmBundle\Tests\Ldap\UtilTest;

class Util
{
    // round((1970-1601)/4) = round(92.25) = 92
    const UNIX_EPOCH_DIFFERENCE = ((1970-1601) * 365 - 3 + 92 ) * 86400;
    const LDAP_DATETIME_FORMAT = 'YmdHis';
    const AD_INT64_MIN_VALUE = -9223372036854775808;

    /**
     * Convert an LDAP timestamp to a PHP DateTime
     * @param $input
     * @return \DateTime
     */
    public static function ldapDateToDatetime($input) {
        return \DateTime::createFromFormat(self::LDAP_DATETIME_FORMAT, str_replace('Z', '', $input));
    }

    /**
     * Convert a PHP DateTime to an LDAP timestamp
     * @param \DateTime $input
     * @return string
     */
    public static function datetimeToLdapDate(\DateTime $input) {
        return $input->format(self::LDAP_DATETIME_FORMAT).'Z';

    }

    /**
     * Convert an AD timestamp to a PHP DateTime
     * @param $input
     * @return \DateTime
     */
    public static function adDateToDatetime($input)

    {
        if (strlen($input) > 17) {
            $adMilliseconds = (float)str_replace('.0Z', '', $input);
            $adSeconds = number_format($adMilliseconds / 10000000.0, 2, '.', '');
            list($adSeconds, $adMilliseconds) = explode('.', $adSeconds);
            $unixTimestamp = $adSeconds - self::UNIX_EPOCH_DIFFERENCE;
            $dt = \DateTime::createFromFormat('U.u', $unixTimestamp.'.'.$adMilliseconds);
        } else {
            $adSeconds = (float)str_replace('.0Z', '', $input);
            $dt = self::ldapDateToDatetime($adSeconds);
        }
        $dt->setTimezone((new \DateTime())->getTimezone());


        return $dt;
    }

    /**
     * Convert a PHP DateTime to an AD timestamp.
     *
     * An AD timestamp is the number of 100-nanoseconds intervals
     * (1 nanosecond = one billionth of a second) since Jan 1, 1601 UTC.
     * @param \DateTime $input
     * @return string
     */
    public static function datetimeToAdDate(\DateTime $input) {
        $unixTimestamp = $input->format('U'); // Note! This conversts to a UTC timestamp!
        $milliseconds = $input->format('u');
        $adSeconds = $unixTimestamp + self::UNIX_EPOCH_DIFFERENCE;
        $adMilliseconds = $adSeconds  . $milliseconds;
        $adMilliseconds = str_pad($adMilliseconds, (strlen($adMilliseconds)+(18 - strlen($adMilliseconds))), '0');
        return $adMilliseconds;

    }

}