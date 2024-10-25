<?php

use Axpecto\ClassLoader\FileSystemClassLoader;
use Axpecto\Container\Container;
use Axpecto\Test;

// Set display errors to true
ini_set( 'display_errors', '1' );

// Define Reparo constants.
const AXPECTO_SRC_PATH  = __DIR__ . '/Axpecto/';
const AXPECTO_NAMESPACE = 'Axpecto';

// Load Axpecto collections.
require_once dirname( AXPECTO_SRC_PATH ) . '/functions.php';

// Class loader setup.
require_once AXPECTO_SRC_PATH . 'ClassLoader/FileSystemClassLoader.php';
$classLoader = new FileSystemClassLoader();
$classLoader->registerPath( namespace: AXPECTO_NAMESPACE, path: AXPECTO_SRC_PATH );

$container = new Container();
$container->addClassInstance( FileSystemClassLoader::class, $classLoader );
$container->addValue( 'runnerPath', __DIR__ . '/run.php' );
$container->addValue( 'isAxpectoRunner', false );
