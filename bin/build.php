#!/bin/env php
<?php

require __DIR__ . "/../vendor/autoload.php";

chdir(__DIR__);

$data = file_get_contents("../Example/pattern.json");
$array = json_decode($data, true);

$generator = new Cybits\ArrayBuilder\Generator($array);
