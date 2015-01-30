<?php

if(!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);

require_once dirname(__DIR__) . DS . 'vendor' . DS . 'autoload.php';

//TEST CONSTANTS
define('FIXTURES', __DIR__ . DS . 'fixtures');

define("JENKINSCI_ROOT", dirname(__DIR__));

define('BOOTSTRAP', __FILE__);

require_once __DIR__ . DS . 'TestBase.php';
