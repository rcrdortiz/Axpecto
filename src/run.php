<?php
// Load Axpecto
use Axpecto\Container\Container;

sleep( 5 );

//include 'load.php';
file_put_contents( './test.txt', 'test' );
echo "We did it";

exit( 0 );

//exit( 0 );
/** @var Container $container */
$container->addValue( 'isAxpectoRunner', true );
$container->addValue( 'isAxpectoRunner', true );
$container->addValue( 'isAxpectoRunner', true );


//var_dump( $argv );
