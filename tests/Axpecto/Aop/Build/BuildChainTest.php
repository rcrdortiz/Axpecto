<?php

namespace Axpecto\Aop\Build;

use Axpecto\Aop\Annotation;
use Axpecto\Aop\BuildHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit Test for BuildChain class.
 */
class BuildChainTest extends TestCase {

	/**
	 * Test that BuildChain proceeds without any annotations.
	 */
	public function testProceedWithoutAnnotations() {
		// Create an empty Klist and BuildOutput
		$output = new BuildOutput();

		// Create the BuildChain
		$chain = new BuildChain( emptyList(), $output );

		// Assert that the chain proceeds and returns the original output
		$this->assertSame( $output, $chain->proceed() );
	}

	/**
	 * Test that BuildChain correctly processes an annotation with a builder.
	 */
	public function testProceedWithAnnotation() {
		// Mock the BuildOutput object
		$output = $this->createMock( BuildOutput::class );

		// Create a mock annotation with a builder
		$annotation = $this->createMock( Annotation::class );
		$builder    = $this->createMock( BuildHandler::class );

		// Set up the mock annotation to return the builder
		$annotation->expects( $this->once() )
		           ->method( 'getBuilder' )
		           ->willReturn( $builder );

		// Set up the builder to intercept and return a modified BuildOutput
		$modifiedOutput = $this->createMock( BuildOutput::class );
		$builder->expects( $this->once() )
		        ->method( 'intercept' )
		        ->with(
			        $this->isInstanceOf( BuildChain::class ),
			        $this->isInstanceOf( Annotation::class ),
			        $this->isInstanceOf( BuildOutput::class )
		        )
		        ->willReturn( $modifiedOutput );

		// Create the BuildChain with the mock annotation in a Klist
		$klist = listOf( $annotation );
		$chain = new BuildChain( $klist, $output );

		// Assert that the chain proceeds and returns the modified output
		$this->assertSame( $modifiedOutput, $chain->proceed() );
	}

	/**
	 * Test that BuildChain skips annotations without a builder.
	 */
	public function testProceedWithAnnotationWithoutBuilder() {
		// Mock the BuildOutput object
		$output = $this->createMock( BuildOutput::class );

		// Create a mock annotation without a builder
		$annotation = $this->createMock( Annotation::class );

		// Set up the mock annotation to return null for the builder
		$annotation->expects( $this->once() )
		           ->method( 'getBuilder' )
		           ->willReturn( null );

		// Create the BuildChain with the mock annotation in a Klist
		$klist = listOf( $annotation );
		$chain = new BuildChain( $klist, $output );

		// Assert that the chain proceeds and returns the original output
		$this->assertSame( $output, $chain->proceed() );
	}
}
