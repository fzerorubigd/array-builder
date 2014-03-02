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

    protected function __construct()
    {
    }

    /**
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
        if ($this->internalHasProperty($name)) {
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
            return true;
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
                            '_', $scored
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
