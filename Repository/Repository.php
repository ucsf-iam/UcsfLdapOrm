<?php

/* * *************************************************************************
 * Copyright (C) 1999-2013 Gadz.org                                        *
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
 * ************************************************************************* */

namespace Ucsf\LdapOrmBundle\Repository;

use Ucsf\LdapOrmBundle\Ldap\LdapEntityManager;
use Ucsf\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Ucsf\LdapOrmBundle\Ldap\Filter\LdapFilter;

/**
 * Repository for fetching ldap entity
 */
class Repository {

    protected $em, $it;
    private $class;
    private $entityName;

    /**
     * Build the LDAP repository for the given entity type (i.e. class)
     * 
     * @param LdapEntityManager $em
     * @param ReflectionClass   $reflectorClass
     */
    public function __construct(LdapEntityManager $em, ClassMetaDataCollection $class) {
        $this->em = $em;
        $this->class = $class;
        $this->entityName = $class->name;
    }


    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return array|object The found entity/entities.
     * @throws BadMethodCallException  If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     */
    public function __call($method, $arguments) {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = lcfirst(substr($method, 6));
                $method = 'findBy';
                break;

            case (0 === strpos($method, 'findOneBy')):
                $by = lcfirst(substr($method, 9));
                if ($this->class->getMeta($by) == null) {
                    throw new \BadMethodCallException("No sutch ldap attribute $by in $this->entityName");
                }
                $method = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with " .
                    "either findBy or findOneBy!"
                );
        }
        return $this->$method(
                    $by, // attribute name
                    $arguments[0], // attribute value
                    empty($arguments[1]) ? null : $arguments[1] // attribute list
                );
    }

    /**
     * Create a simple LDAP filter for the current respository by specifying
     * the analogous objectClass. If attribute name & value are supplied
     * the filter will also contain a constraint for that attribute.
     *
     * Also saves previously used filters objects to prevent excessive memory footprint usage
     * 
     * @param type $varname
     * @param type $value
     * @return An LdapFilter
     */
    private function getFilter($varname = false, $value = false) {
        static $allFilters = array();
        if ($varname === false) {
            $attribute = 'objectClass';
            $value = $this->class->getObjectClass();
        } else {
            $attribute = $this->class->getMeta($varname);
        }
        if (!isset($allFilters[$key = base64_encode($this->class->getObjectClass() . $attribute . $value)])) {
            $allFilters[$key] = new LdapFilter(array(
                $attribute => $value,
                'objectClass' => $this->class->getObjectClass(),
            ));
        }
        return $allFilters[$key];
    }

    /**
     * Simple LDAP search for all entries within the current repository
     * @return An array of LdapEntity objects
     */
    public function findAll($attributes = null) {    
        $options = array();
        if ($attributes != null) {
            $options['attributes'] = $attributes;
        }
        return $this->em->retrieve($this->entityName, $options);        
    }

    /**
     * Simple LDAP search with a single attribute name/value pair 
     * within the current repository
     * @param string $varname LDAP attribute name
     * @param string $value LDAP vattribute value
     * @return An array of LdapEntity objects
     */
    public function findBy($varname, $value, $attributes = null) {
        $options = array();
        $options['filter'] = new LdapFilter(array($varname => $value));
        if ($attributes != null) {
            $options['attributes'] = $attributes;
        }
        return $this->em->retrieve($this->entityName, $options);
    }

    
    /**
     * Return an object or objects with corresponding varname as Criteria.
     * @todo This should return an error when more than one is found
     * @param string $varname LDAP attribute name
     * @param string $value LDAP vattribute value
     * @return An LdapEntity

     */
    public function findOneBy($varname, $value, $attributes) {
        $r = $this->findBy($varname, $value, $attributes);
        if (empty($r[0])) {
            return array();
        } else {
            return $r[0];
        }        
    }


    /**
     * Create a complex LDAP filter string from an multi-dimensional array of LDAP filter
     * operators and operands.
     *
     * $mixed is always an associative array at the top level, with the key containing a LDAP
     * filter operator and the value containing another associative array that can:
     * 
     * 1. Associate an LDAP field with a filtering value, for example:
     * 
     * array($attributeName =>  $attributeValue.'*')
     * 
     * This applies a single attribute filter where the key is the attribute and the value 
     * is a proper LDAP filter value, with asterisks (*), etc.  So it would produce a filter
     * like this:
     * 
     * (someName=someValue*)
     * 
     * 2. Associate an LDAP field with a sequential (i.e. not associative) array, for example:
     * 
     * array($attributeName => array($attributeValue1, '*'.$attributeValue2.'*', '* '.$attributeValue3))
     * 
     * This generate an '|' (or) filter on the attribute for the 3 given filter values, like this:
     * 
     * (|(someName=someValue1)(someName=*someValue2*)(someName=* someValue3))
     * 
     * 3. Associate another operator with another associative array. For example:
     *
     *   array(
     *       '&' => array(
     *           'key3' => 'val3',
     *           'key4' => 'val4',
     *           '|' => array(
     *               'key1' => 'val1',
     *               'key2' => array('val2a', 'val2b'),
     *               '&' => array(
     *                   'key5' => 'val5',
     *                   'key6' => 'val6',
     *               ),
     *               array( // this is how you get multiple & under on |
     *                   '&' => array(
     *                       'key7' => 'val7',
     *                       'key8' => 'val8',
     *                   )
     *               ),
     *               array(
     *                   '&' => array(
     *                       'key9' => 'val9',
     *                       'key20' => 'val10',
     *                   )
     *               )
     *           )
     *       )
     *   )
     *
     * would produce:
     *
     * (&
     *  (key3=val3)
     *  (key=val4)
     * (|
     *   (key1=val1)
     *   (|(key2=val2a)(key2=val2b))
     *   (&
     *      (key5=val5)
     *      (key6=val6)
     *
     * @param array $mixed An associative array of LDAP filter operators and operands
     * @return string LDAP filter string
     * @see \Ucsf\LdapOrmBundle\Ldap\Filter\LdapFilter::createComplexLdapFilter($mixed)
     */
    public function findByComplex($filterArray, $attributes = null) {
        return $this->em->retrieve(
            $this->entityName,
            array(
                'filter' => new LdapFilter($filterArray),
                'attributes' => $attributes
            )
        );
    }

   /**
     * Uses the new Iterator in LdapEntityManager to return the first element of a search
     * 
     * Returns false if there are no more objects in the iterator
     */
    public function itFindFirst($varname = false, $value = false) {
        if (empty($this->it)) {
            $this->it = $this->em->getIterator($this->getFilter($varname, $value), $this->entityName);
        }
        return $this->it->first();
    }

    /**
     * Uses the new Iterator in LdapEntityManager to return the next element of a search
     * 
     * Returns false if there are no more objects in the iterator
     */
    public function itGetNext($varname = false, $value = false) {
        if (empty($this->it)) {
            $this->it = $this->em->getIterator($this->getFilter($varname, $value), $this->entityName);
        }
        return $this->it->next();
    }

    /**
     * Verify that we are at the beggining of the iterator
     *
     * @return boolean 
     */
    public function itBegins() {
        return isset($this->it) ? $this->it->isFirst() : false;
    }

    /**
     * Verify that we are at the end of the iterator
     *
     * @return boolean 
     */
    public function itEnds() {
        return isset($this->it) ? $this->it->isLast() : false;
    }

    /**
     * Removes an iterator 
     */
    public function itReset() {
        unset($this->it);
    }
    
    
}
