<?php

namespace Cybits\ArrayBuilder;

/**
 * Class Runtime, Base class for all generated class
 *
 * @package Cybits\ArrayBuilder
 */
class Runtime implements \JsonSerializable
{

    protected $validProperties = true;
    private $data = array();

    /**
     * make constructor protected to force using the create method
     */
    protected function __construct()
    {
    }

    /**
     * Create new instance of this object
     *
     * @return Runtime
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Magic call method
     *
     * @param string $name      the function
     * @param array  $arguments the argument
     *
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $sign = substr($name, 0, 3);
        if ($sign == 'set') {
            return $this->set($this->camelize(substr($name, 3)), array_shift($arguments));
        } elseif ($sign == 'get') {
            return $this->get($this->camelize(substr($name, 3)));
        } elseif ($sign == 'add') {
            $key = array_shift($arguments);
            $value = array_shift($arguments);

            return $this->add($this->camelize(substr($name, 3)), $key, $value);
        }

        throw new \BadMethodCallException("Invalid function $name");
    }

    /**
     * Set a key on array
     *
     * @param string $name  the key name
     * @param mixed  $value value
     *
     * @throws \BadMethodCallException
     * @return Runtime
     */
    public function set($name, $value)
    {
        if ($type = $this->internalHasProperty($name)) {
            if ($type == 'array' && !is_array($value) && null === $value) {
                throw new \BadMethodCallException("The $name is an array");
            }
            $this->data[$name] = $value;

            return $this;
        }

        throw new \BadMethodCallException("Invalid property $name");
    }

    /**
     * Get if the property is available
     *
     * @param string $property the property name
     *
     * @return bool
     */
    private function internalHasProperty($property)
    {
        if ($this->validProperties === true || isset($this->validProperties[$property])) {
            return $this->validProperties[$property];
        }

        return false;
    }

    /**
     * Transforms an under_scored_string to a camelCasedOne
     *
     * @param string $scored the_string
     * @param string $glue   the glue
     *
     * @return string TheString
     */
    protected function camelize($scored, $glue = '')
    {
        return lcfirst(
            implode(
                $glue,
                array_map(
                    'ucfirst',
                    array_map(
                        'strtolower',
                        explode(
                            '_',
                            $scored
                        )
                    )
                )
            )
        );
    }

    /**
     * Get a key from array
     *
     * @param string $name the key
     *
     * @throws \BadMethodCallException
     * @return mixed
     */
    public function get($name)
    {
        if ($this->internalHasProperty($name)) {
            return isset($this->data[$name]) ? $this->data[$name] : null;
        }

        throw new \BadMethodCallException("Invalid property $name");
    }

    /**
     * Add a key to array, if property is null then add it to self array
     *
     * @param string      $key      the array key
     * @param string|null $property the array property
     * @param mixed       $value    the value to set
     *
     * @throws \BadMethodCallException
     * @return Runtime
     */
    public function add($key, $property, $value)
    {
        if (!isset($property)) {
            return $this->set($key, $value);
        }

        if ($type = $this->internalHasProperty($property)) {
            if ($type != 'array') {
                throw new \BadMethodCallException("$property is not an array");
            }

            if (!isset($this->data[$property])) {
                $this->data[$property] = array();
            }

            $this->data[$property][$key] = $value;
        }

        throw new \BadMethodCallException("Invalid property $property");
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *       which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Export current object to real array
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->data;

        foreach ($result as &$value) {
            if ($value instanceof Runtime) {
                $value = $value->toArray();
            }
        }

        return $result;
    }
}
