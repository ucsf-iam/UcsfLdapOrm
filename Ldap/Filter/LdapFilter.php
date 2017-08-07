<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/
 
namespace Ucsf\LdapOrmBundle\Ldap\Filter;

use Ucsf\LdapOrmBundle\Exception\Filter\InvalidLdapFilterException;
use Ucsf\LdapOrmBundle\Ldap\Util;

class LdapFilter
{
    
	private $filterArray;
    private $forActiveDirectory;

	function __construct($filterArray=null, $forActiveDirectory = false)
	{
            if (empty($filterArray)) {
                $this->filterArray = array('objectClass' => '*');
            } else {
                if (!is_array($filterArray)) {
                    throw new InvalidLdapFilterException('Filter data must be provided in an associative array.');
                }
                $this->filterArray = $filterArray;
            }
            $this->forActiveDirectory = $forActiveDirectory;
	}
        
        /**
         * Create a complex LDAP filter string from an multi-dimensional associative array of LDAP filter
         * operators, attributes and values. By default comparisons are based upon equality ('='), however
         * adding a '<' or '>' will change the comparator to '<=' or '>=', respectively.  Here are some examples
         * of the basic building blocks:
         * 
         * 1. Filter with an attribute of a given value with a simple associative array, for example:
         * 
         * $attributeName = 'color';
         * $attributeValue= 'green';
         * array($attributeName =>  $attributeValue.'*')
         * 
         * This applies a single attribute filter where the key is the attribute and the value 
         * is string filter value, in this case using asterisk (*) as a wildcard.  This generates a filter
         * like this:
         * 
         * (color=green*)
         * 
         * 2. Filter on an attribute with a set of possible values with an associative array that has the attribute
         * as the key and an indexed array as the value. This following example will generate an '|' (or) filter on
         * the attribute for the 3 given filter values.
         * 
         * $attributeName = 'color';
         * $attributeValue1= 'green';
         * $attributeValue2= 'red';
         * $attributeValue3= 'blue';
         * array($attributeName => array($attributeValue1, '*'.$attributeValue2.'*', '* '.$attributeValue3))
         * 
         * will become:
         * 
         * (|(color=green)(color=*red*)(color=* blue))
         * 
         * 3. Finally, to create truly complex queries, associate an operator with another associative array. Note in
         * the example below the output has 3 '&' clauses at the same level. Were these set at the same level in the 
         * associate array, the last one would overwrite the first two. To avoid this, the multiple '&' clauses are 
         * each put with a single-index array.
         *
         *   array(
         *       '&' => array(
         *           'key3' => 'val3',
         *           'key4' => 'val4',
         *           '|' => array(
         *               'key1' => 'val1',
         *               'key2' => array('val2a', 'val2b'),
         *               array( // this is how you get multiple & under a | without clobbering previous '&' keys
         *                   '&' => array(
         *                       'key5' => 'val5',
         *                       'key6' => 'val6',
         *                   )
         *               ),
         *               array(
         *                   '&' => array(
         *                       'key7' => 'val7',
         *                       'key8' => 'val8',
         *                   )
         *               ),
         *               array(
         *                   '&' => array(
         *                       'key9' => 'val9',
         *                       'key10' => 'val10',
         *                   )
         *               )
         *           )
         *       )
         *   )
         *
         * would produce:
         *
         * (&
         *   (key3=val3)
         *   (key4=val4)
         *   (|
         *     (key1=val1)
         *     (|(key2=val2a)(key2=val2b))
         *     (&(key5=val5)(key6=val6))
         *     (&(key7=val7)(key8=val8))
         *     (&(key9=val9)(key10=val10))
         *   )
         * )
         *
         * @return string An LDAP filter
         */
    public function format() {
        return self::_format($this->filterArray, $this->forActiveDirectory);
    }

    function getFilterArray() {
        return $this->filterArray;
    }

    function setFilterArray($filterArray) {
        $this->filterArray = $filterArray;
    }

    public static function _format($filterData, $forActiveDirectory) {
        if (!is_array($filterData)) {
            throw new InvalidLdapFilterException('The filter must be an array');
        }        
        if (is_array($filterData)) {
            $subfilter = '';
            foreach ($filterData as $key => $val) {
                if (is_numeric($key)) { // occures when applying same operator (e.g. array key) on a list of key/value pairs
                    $key = key($val);
                    $val = array_pop($val);
                }
                if (is_array($val)) { // complex filter
                    if (!(bool) count(array_filter(array_keys($val), 'is_string'))) { // if not assoc array, i.e.  OR multiple values 
                        $multivalue = '';
                        foreach ($val as $subval) {
                            $op = '=';
                            if (preg_match('/([><])$/', $key, $matches)) {
                                $key = substr($key, 0, -1);
                                $op = $matches[1];
                            }
                            if (preg_match('/([><]=)$/', $key, $matches)) {
                                $key = substr($key, 0, -2);
                                $op = $matches[1];
                            }
                            if (is_array($subval)) {
                                $subval = self::_format($subval);
                            } else {
                                $subval = self::escapeLdapValue($subval);
                            }
                            $multivalue .= '(' . $key . $op . $subval . ')';
                        }
                        $subfilter .= '(|' . $multivalue . ')';
                    } else { // iterate into complex sub-filter
                        $subfilter .= '(' . $key . self::_format($val, $forActiveDirectory) . ')';
                    }
                } else { // simple filter
                    $op = '=';
                    if (preg_match('/([><])$/', $key, $matches)) {
                        $key = substr($key, 0, -1);
                        $op = $matches[1];
                    }
                    if (preg_match('/([><]=)$/', $key, $matches)) {
                        $key = substr($key, 0, -2);
                        $op = $matches[1];
                    }
                    if (is_a($val, \DateTime::class)) {
                        if ($forActiveDirectory) {
                            $val = Util::datetimeToAdDate($val);
                        } else {
                            $val = Util::datetimeToLdapDate($val);

                        }
                    } else {
                        $val = self::escapeLdapValue($val);
                    }

                    $subfilter .= '(' . $key . $op . $val . ')';
                }
            }

            return $subfilter;
        } else {
            return $filterData;
        }
    }

    /**
     * @param $val
     * @return mixed
     */
    public static function escapeLdapValue($val) {
        return str_replace(
                array('=', ',', '(', ')'),
                array('\\3d', '\\2c', '\\28', '\\29'),
                $val);
    }

}
