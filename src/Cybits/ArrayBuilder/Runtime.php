<?php

namespace Cybits\ArrayBuilder;

/**
 * Class Runtime, Base class for all generated class
 *
 * @package Cybits\ArrayBuilder
 */
class Runtime implements \JsonSerializable
{

    private $validProperties = true;
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
            return $this->set($this->unCamelize(substr($name, 3)), array_shift($arguments));
        } elseif ($sign == 'get') {
            return $this->get($this->unCamelize(substr($name, 3)));
        } elseif ($sign == 'add') {
            $key = array_shift($arguments);
            $value = array_shift($arguments);

            return $this->add($key, $this->unCamelize(substr($name, 3)), $value);
        } elseif (($sign = substr($name, 0, strlen('append'))) == 'append') {
            $value = array_shift($arguments);
            $key = array_shift($arguments);
            $property = substr($name, strlen('append'));

            return $this->add($key, $property, $value);
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
        $valid = $this->getValidProperties();
        if ($valid === true) {
            return true;
        }
        if (isset($valid[$property])) {
            return $valid[$property];
        }

        return false;
    }

    /**
     * Get the default properties for current object
     *
     * @return bool|array
     */
    public function getValidProperties()
    {
        return $this->validProperties;
    }

    /**
     * Transforms a camelCasedString to an under_scored_one
     *
     * @param string $camelCase the camel case string
     * @param string $glue      the glue, default to _
     *
     * @return string
     */
    public function unCamelize($camelCase, $glue = '_')
    {
        return implode(
            $glue,
            array_map(
                'strtolower',
                preg_split(
                    '/([A-Z]{1}[^A-Z]*)/',
                    $camelCase,
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
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
        $any = false;
        $type = $this->internalHasProperty($property);
        if (!$type) {
            $type = $this->internalHasProperty('_' . $this->unCamelize($property));
            $any = true;
        }
        if ($type) {
            if ($type != 'array') {
                throw new \BadMethodCallException("$property is not an array");
            }

            if ($any) {
                if ($key) {
                    $this->data[$key] = $value;
                } else {
                    $this->data[] = $value;
                }
            } else {
                if ($key) {
                    $this->data[$property][$key] = $value;
                } else {
                    $this->data[$property][] = $value;
                }
            }

            return $this;
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
        return $this->iterateArray($this->data);
    }

    /**
     * Iterate over the internal array
     *
     * @param array $array iterate over array
     *
     * @return array
     */
    private function iterateArray(array $array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            if ($value instanceof Runtime) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = $this->iterateArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
