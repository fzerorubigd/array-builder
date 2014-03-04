<?php

class Autoloader
{
    /** @var \Composer\Autoload\ClassLoader $loader */
    protected static $loader;
    /**
     * Make this class a singleton
     */
    protected function __construct()
    {
    }

    /**
     * Get the loader
     *
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (!self::$loader) {
            self::$loader = require __DIR__ . "/../vendor/autoload.php";
        }

        return self::$loader;
    }
}

//Make sure its initialized :)
Autoloader::getLoader();
