# Axpecto

[![GitHub](https://img.shields.io/github/license/JsonMapper/JsonMapper)](https://choosealicense.com/licenses/mit/)

Axpecto is a magically versatile PHP library that brings the power of aspect-oriented programming (AOP), dependency injection (DI), and enhanced collections to your projects. With Axpecto, you can seamlessly add cross-cutting concerns, manage object dependencies, and work with collections in a more efficient and intuitive way.



## Features

- Aspect-oriented engine for adding cross-cutting concerns to your code.
- Dependency injection container for managing object dependencies.
- Improved collections for working with lists, maps, and data sets.

## Installation

You can install the library via Composer. Run the following command:

```
composer require rcrdortiz/axpecto
```

## Usage
### Aspect-Oriented Engine
To use the aspect-oriented engine, follow these steps:

Define your aspects using annotations.
Apply the aspects to your methods.
You can view a sample of how to create and use aspects in the examples directory where we explore a simple caching aspect.

### Dependency Injection Container
To use the dependency injection container, follow these steps:
Include a classloader in your project or the classloader included in Axpecto.

``` 
$clasLoader = new FileSystemClassLoader();
// Register paths to your classes.
$clasLoader->registerPath( 'Axpecto', AXPECTO_SRC );
$clasLoader->registerPath( 'Examples', ROOT  . 'Examples' . DIRECTORY_SEPARATOR );
```

Once you have your classloader configured you can instantiate the container and start using it.

```
$container = new Container();

// Retrieve your main application or component from the container.
$app = $container->get( Application::class );
```

And that's it. You can now start using the container to manage your object dependencies.
By default the container will auto-wire your objects so you don't have to do anything, but you can also define your services, parameters and bind concrete classes to interfaces manually.

````
$container = new Container();
 
// Bind interface example
$container->addImplementation( CacheInterface::class, InMemoryCache::class );

// Bind service example
$container->addClassInstance( CacheInterface::class, new CustomCache( 'value1', 'valueN' ) );

// Bind parameter example
$container->addValue( 'cache_ttl', 3600 );
````

### Improved Collections
To start using the improved collections, follow these steps:

```
// Include the functions file.
require_once 'path_to_axpecto/src/functions.php';

$myData = listOf( 1, 3, 5, 7, 9 );

// Lists are immutable, so you can chain operations without modifying the original list.
$greaterThanTen = $myData->map( fn ( $item ) => $item * 3 )
       ->filter( fn ( $item ) => $item > 10 );

// You can also switch to a mutable list.
$mutableData = $myData->toMutable();
$mutableData->filter( fn( $item ) => $item > 5 );
$hasOne = $mutableData->any( fn( $item ) => $item === 1 ) );
$asString = "My favorite numbers are " . $mutableData->join( ', ' );
```


## Contributing
Contributions are welcome! Please see the Contributing Guidelines for more information.

## License
See the LICENSE.txt file for details.

