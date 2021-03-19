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
 
namespace Ucsf\LdapOrmBundle\Mapping;


class ClassMetaDataCollection
{
    private $metadatas;
    private $repository;
    public $name;
    public $arrayOfLink;
    public $sequences;
    public $dnRegex;
    public $parentLink;
    public $objectClass;
    public $searchDn;
    public $uniqueIdentifier;
    public $dn;
    public $arrayField;
    public $must;
    public $operational;

    public function __construct()
    {
        $this->metadatas        = array();
        $this->reverseMetadatas = array();
        $this->arrayOfLink      = array();
        $this->dnRegex          = array();
        $this->parentLink       = array();
        $this->arrayField       = array();
        $this->must             = array();
        $this->operational      = array();
    }

    public function addArrayField($fieldName)
    {
        $this->arrayField[$fieldName] = true;
    }
    
    public function addMust($fieldName)
    {
        $this->must[$fieldName] = true;
    }

    public function addOperational($fieldName)
    {
        $this->operational[$fieldName] = true;
    }

    public function isArrayField($fieldName)
    {
        if(isset($this->arrayField[$fieldName])) {
            return $this->arrayField[$fieldName];
        }

        return false;
    }

    public function setObjectClass($objectClass) {
        $this->objectClass = $objectClass;
    }

    public function getObjectClass() {
        return $this->objectClass;
    }
    function getSearchDn() {
        return $this->searchDn;
    }

    function setSearchDn($searchDn) {
        $this->searchDn = $searchDn;
    }
    function getDn() {
        return $this->dn;
    }

    function setDn($dn) {
        $this->dn = $dn;
    }

        public function getKey($value) 
    {
        if(isset($this->reverseMetadatas[$value])) {
            return $this->reverseMetadatas[$value];
        }
        return null;
    }
    
    public function addMeta($key, $value)
    {
        $this->metadatas[$key] = $value;
        $this->reverseMetadatas[$value] = $key;
    }
    
    public function getMeta($key)
    {
        // normalize attribute name to find in meta
        $metadatas = array_change_key_case($this->metadatas);
        $key = strtolower($key);

        if(isset($metadatas[$key])) {
            return $metadatas[$key];
        }
        return null;
    }
    
    public function getMetadatas()
    {
        return $this->metadatas;
    }
    
    public function setMetadatas($metadatas)
    {
        $this->metadatas = $metadatas;
    }

    public function addArrayOfLink($key, $class)
    {
        $this->arrayOfLink[$key] = $class;
    }

    public function isArrayOfLink($key)
    {
        return isset($this->arrayOfLink[$key]);
    }

    public function getArrayOfLinkClass($key)
    {
        return $this->arrayOfLink[$key];
    }

    public function addSequence($key, $dn)
    {
        $this->sequence[$key] = $dn;
    }

    public function isSequence($key)
    {
        return isset($this->sequence[$key]);
    }

    public function getSequence($key)
    {
        return $this->sequence[$key];
    }

    public function addParentLink($key, $dn)
    {  
        $this->parentLink[$key] = $dn;
    }

    public function getParentLink()
    {  
        return $this->parentLink;
    }

    public function addRegex($key, $regex)
    {
        $this->dnRegex[$key] = $regex;
    }

    public function getDnRegex()
    {
        return $this->dnRegex;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }
    
    function getMust() {
        return $this->must;
    }

    function setMust($must) {
        $this->must = $must;
    }

    /**
     * @return array
     */
    public function getOperational()
    {
        return $this->operational;
    }

    /**
     * @param array $operational
     */
    public function setOperational($operational)
    {
        $this->operational = $operational;
    }

    /**
     * @return mixed
     */
    public function getUniqueIdentifier()
    {
        return $this->uniqueIdentifier;
    }

    /**
     * @param mixed $uniqueIdentifier
     */
    public function setUniqueIdentifier($uniqueIdentifier)
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
    }




}
