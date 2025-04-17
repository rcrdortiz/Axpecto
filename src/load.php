<?php

use Axpecto\ClassLoader\FileSystemClassLoader;
use Axpecto\Container\Container;

// Define Axpecto constants.
const AXPECTO_SRC_PATH  = __DIR__ . '/Axpecto/';
const AXPECTO_NAMESPACE = 'Axpecto';

// Load Axpecto collections.
require_once dirname( AXPECTO_SRC_PATH ) . '/functions.php';

// Class loader setup.
require_once AXPECTO_SRC_PATH . 'ClassLoader/FileSystemClassLoader.php';
$classLoader = new FileSystemClassLoader();
$classLoader->registerPath( namespace: AXPECTO_NAMESPACE, path: AXPECTO_SRC_PATH );

// Create the Axpecto container.
global $axpectoContainer;
$axpectoContainer = new Container();
$axpectoContainer->addClassInstance( FileSystemClassLoader::class, $classLoader );
