<?php

use Axpecto\Loader\FileSystemClassLoader;

require_once dirname( __FILE__, 2 ) . '/vendor/autoload.php';
require_once dirname( __FILE__, 2 ) . '/src/Axpecto/Loader/FileSystemClassLoader.php';
require_once dirname( __FILE__, 2 ) . '/src/functions.php';

$clasLoader = new FileSystemClassLoader();
$clasLoader->registerPath( 'Axpecto',
                           dirname( __FILE__, 2 ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Axpecto' . DIRECTORY_SEPARATOR );