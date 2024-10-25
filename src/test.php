<?php
// Load Axpecto
use Axpecto\Container\Container;
use Axpecto\Test;

include 'load.php';

/** @var Container $container */
$container->get( Test::class )->callMethod();

die( 'done doing things' );