<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

use Axpecto\Container\Container;
use Axpecto\Loader\FileSystemClassLoader;
use Examples\Caching\Application;

define( 'ROOT', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );
const AXPECTO_ROOT = ROOT . 'src' . DIRECTORY_SEPARATOR;
const AXPECTO_SRC = AXPECTO_ROOT . 'Axpecto' . DIRECTORY_SEPARATOR;
require_once AXPECTO_ROOT    . '/functions.php';
require_once AXPECTO_ROOT    . '/Axpecto/Loader/FileSystemClassLoader.php';

$clasLoader = new FileSystemClassLoader();
$clasLoader->registerPath( 'Axpecto', AXPECTO_SRC );
$clasLoader->registerPath( 'Examples', ROOT  . 'Examples' . DIRECTORY_SEPARATOR );

$container = new Container();

$app = $container->get( Application::class );

echo "\nStarting message is " . $app->getMessage();
for( $i = 0; $i < 13; $i++ ) {
	echo "\nApplication time is: " . $app->getTime();
	sleep( 1 );
	$app->setMessage( 'Message ' . $i );
}
echo "\nEnding message is " . $app->getMessage();
