<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Repository\Mapper\ArrayToEntityMapper;
use Axpecto\Repository\Repository;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity as EntityAttribute;
use Axpecto\Storage\Entity\Mapping;
use Exception;
use Override;
use ReflectionMethod;

class RepositoryBuildHandler implements BuildHandler {

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param ReflectionUtils            $reflectUtils
	 * @param RepositoryMethodNameParser $nameParser
	 */
	public function __construct(
		private readonly ReflectionUtils $reflectUtils,
		private readonly RepositoryMethodNameParser $nameParser,
	) {
	}

	#[Override]
	public function intercept( Annotation $annotation, BuildContext $context ): void {
		if ( ! $annotation instanceof Repository || $annotation->getAnnotatedMethod() !== null ) {
			return;
		}

		/** @var EntityAttribute $entityAnnotation */
		$entityAnnotation = $this->reflectUtils
			->getClassAnnotations( $annotation->entityClass, EntityAttribute::class )
			->firstOrNull();

		if ( ! $entityAnnotation ) {
			return;
		}

		$this->reflectUtils
			->getAbstractMethods( $annotation->getAnnotatedClass() )
			->foreach( fn( ReflectionMethod $m ) => $this->implementAbstractMethod( $m, $context, $annotation, $entityAnnotation ) );
	}

	/**
	 * Implements an abstract method by generating code that creates a Criteria,
	 * adds conditions based on parsed method parts (using the mapped field name if defined),
	 * and returns the storage call result.
	 *
	 * @param ReflectionMethod $method
	 * @param BuildContext     $output
	 * @param Repository       $repositoryAnnotation
	 * @param EntityAttribute  $entityAnnotation
	 *
	 * @throws Exception
	 *
	 * @TODO Re-implement this whole method.
	 */
	protected function implementAbstractMethod(
		ReflectionMethod $method,
		BuildContext $output,
		Repository $repositoryAnnotation,
		EntityAttribute $entityAnnotation
	): void {
		$entityClass = $repositoryAnnotation->entityClass;

		// Inject dependencies: the mapper and the storage.
		$mapperReference  = $output->injectProperty( 'mapper', ArrayToEntityMapper::class );
		$storageReference = $output->injectProperty( 'storage', $entityAnnotation->storage );

		// Build an associative map of entity property => database field using the Mapping annotation.
		$entityFieldMapping = $this->reflectUtils
			->getConstructorArguments( $entityClass )
			->mapOf( function ( $arg ) use ( $entityClass ) {
				$mapping = $this->reflectUtils
					->getParamAnnotations( $entityClass, '__construct', $arg->name, Mapping::class )
					->firstOrNull();

				return [ $arg->name => $mapping ? $mapping->fromField : $arg->name ];
			} );

		// Parse the method name into parts (ParsedMethodPart instances).
		$methodParts     = $this->nameParser->parse( $method->getName() );
		$methodName      = $method->getName();
		$methodSignature = $this->reflectUtils->getMethodDefinitionString( $method->class, $methodName );

		// Calculate the expected argument count from the parsed method parts.
		$expectedCount = 0;
		foreach ( $methodParts->toArray() as $part ) {
			if ( empty( $part->field ) ) {
				continue;
			}
			if ( $part->operator === Operator::BETWEEN ) {
				$expectedCount += 2;
			} elseif ( $part->operator === Operator::IS_NULL || $part->operator === Operator::IS_NOT_NULL ) {
				// No argument required.
			} else {
				$expectedCount ++;
			}
		}

		// Compare with the declared parameter count of the method.
		$declaredParameters = $method->getParameters();
		$declaredCount      = count( $declaredParameters );
		if ( $declaredCount !== $expectedCount ) {
			throw new \Exception( "Method {$method->getName()} declares {$declaredCount} arguments, but parsed conditions require {$expectedCount}." );
		}

		// Get declared parameter names (as strings with the '$' prefix).
		$paramNames = array_map( fn( $p ) => '$' . $p->getName(), $declaredParameters );

		// Define indentation: 2 tabs.
		$indent = "\t\t";

		// Start building the generated code.
		$code = "\$criteria = new \\Axpecto\\Storage\\Criteria\\Criteria();\n";
		$i    = 0;

		// Iterate over each parsed method part using Klist->foreach.
		$methodParts->foreach( function ( ParsedMethodPart $part ) use ( &$code, &$paramNames, &$i, $entityClass, $entityFieldMapping, $indent, $output ) {
			// If no field is provided, skip this part.
			if ( empty( $part->field ) ) {
				return;
			}
			// Verify that the entity defines this property.
			if ( ! isset( $entityFieldMapping[ $part->field ] ) ) {
				throw new \Exception( "Error building repository {$output->class}. Field '{$part->field}' is not defined in entity '{$entityClass}'." );
			}
			// Use the mapped field name.
			$dbField = $entityFieldMapping[ $part->field ];

			// Generate condition code using the declared parameter names.
			if ( $part->operator === Operator::BETWEEN ) {
				// BETWEEN requires two parameters.
				$code .= $indent . "\$criteria->addCondition('{$dbField}', [{$paramNames[$i]}, {$paramNames[$i+1]}], \\Axpecto\\Storage\\Criteria\\Operator::BETWEEN, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n";
				$i    += 2;
			} elseif ( $part->operator === Operator::IS_NULL || $part->operator === Operator::IS_NOT_NULL ) {
				// Operators that require no argument.
				$code .= $indent . "\$criteria->addCondition('{$dbField}', null, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n";
			} else {
				// Otherwise, assume one argument is required.
				$code .= $indent . "\$criteria->addCondition('{$dbField}', {$paramNames[$i]}, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n";
				$i ++;
			}
		} );

		// Generate the final storage call.
		$code .= $indent . "return \$this->{$storageReference}->findAllByCriteria(\$criteria, '{$entityClass}')\n";
		$code .= $indent . "    ->map(fn(\$item) => \$this->{$mapperReference}->map('{$entityClass}', \$item));";

		$output->addMethod(
			name:           $methodName,
			signature:      $methodSignature,
			implementation: $code,
		);
	}
}
