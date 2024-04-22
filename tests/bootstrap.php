<?php

use Enxp\Loader\FileSystemClassLoader;

require_once dirname( __FILE__, 2 ) . '/vendor/autoload.php';
require_once dirname( __FILE__, 2 ) . '/src/Enxp/Loader/FileSystemClassLoader.php';
require_once dirname( __FILE__, 2 ) . '/src/functions.php';

$clasLoader = new FileSystemClassLoader();
$clasLoader->registerPath( 'Enxp',
                           dirname( __FILE__, 2 ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Enxp' . DIRECTORY_SEPARATOR );