<?php

 
namespace Ucsf\LdapOrmBundle\Annotation\Ldap;

/**
 * Annotation to describe an Ldap objectClass
 * 
 * @Annotation
 * @author Jason Gabler <jason.gabler@ucsf.edu>
 */
final class Attribute
{
    private $name;
    private $isOperational = false;

    /**
     * Build the Attribute object
     * 
     * @param array $data
     * 
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        // Treatment of annotation data
        if (isset($data['value'])) {
            $this->name = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * Return the name of the Attribute
     * 
     *  @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the Attribute's name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function setIsOperational($status) {
        $this->isOperational = $status;
    }

    public function getIsOperational() {
        return $this->isOperational;
    }
}
