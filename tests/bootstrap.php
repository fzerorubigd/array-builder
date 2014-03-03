<?php
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . "/../vendor/autoload.php";

$loader->addPsr4('Cybits\\Test\\ArrayBuilder\\', array(__DIR__ . '/_test'));