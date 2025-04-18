<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Annotation\Annotation;
use Axpecto\ClassBuilder\BuildContext;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\Collection\Klist;
use Axpecto\Reflection\ReflectionUtils;
use Axpecto\Repository\Mapper\ArrayToEntityMapper;
use Axpecto\Repository\Repository;
use Axpecto\Storage\Criteria\Operator;
use Axpecto\Storage\Entity\Entity as EntityAttribute;
use Axpecto\Storage\Entity\EntityField;
use Axpecto\Storage\Entity\EntityMetadataService;
use Exception;
use InvalidArgumentException;
use Override;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

readonly class RepositoryBuildHandler implements BuildHandler {

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 *
	 * @param ReflectionUtils $reflectUtils
	 * @param RepositoryMethodNameParser $nameParser
	 * @param EntityMetadataService $metadataService
	 */
	public function __construct(
		private ReflectionUtils $reflectUtils,
		private RepositoryMethodNameParser $nameParser,
		private EntityMetadataService $metadataService,
	) {
	}

	/**
	 * @throws ReflectionException
	 */
	#[Override]
	public function intercept( Annotation $annotation, BuildContext $context ): void {
		if ( ! $annotation instanceof Repository || $annotation->getAnnotatedMethod() !== null ) {
			throw new InvalidArgumentException( 'Invalid annotation type or method.' );
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
	 * @param BuildContext $output
	 * @param Repository $repositoryAnnotation
	 * @param EntityAttribute $entityAnnotation
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

		// Build an associative map of field names to their database field names.
		$fields = $this->metadataService
			->getFields( $entityClass )
			->mapOf( fn( EntityField $field ) => [ $field->name => $field->persistenceMapping ] );

		// Parse the method name into parts (ParsedMethodPart instances).
		$methodParts = $this->nameParser->parse( $method->getName() );

		// Calculate the expected argument count from the parsed method parts.
		$expectedCount = $methodParts->reduce( fn( $count, ParsedMethodPart $part ) => $count + $part->operator->argumentCount(), 0 );

		// Compare with the declared parameter count of the method.
		$declaredParameters = listFrom( $method->getParameters() );
		$declaredCount      = $declaredParameters->count();
		if ( $declaredCount !== $expectedCount ) {
			throw new Exception( "Method {$method->getName()} declares $declaredCount arguments, but parsed conditions require $expectedCount." );
		}

		// Create a list of parameter names for the method.
		$paramNames = $declaredParameters->map( fn( ReflectionParameter $p ) => $p->name );

		// Filter out the method parts that have a field defined and map with the database field names.
		$mappedMethodParts = $methodParts
			->filter( fn( ParsedMethodPart $part ) => $part->field )
			->map( fn( ParsedMethodPart $part ) => $part->copy( field: $fields[ $part->field ] ?? throw new Exception( "Unknown field: $part->field" ) ) );

		$code = $mappedMethodParts->reduce(
			fn( $carry, ParsedMethodPart $part ) => $carry . $this->mapMethodPartToCode( $part, $paramNames ),
			initial: "\$criteria = new \\Axpecto\\Storage\\Criteria\\Criteria();\n"
		);

		// Generate the final storage call.
		$code .= "\t\treturn \$this->{$storageReference}->findAllByCriteria(\$criteria, '{$entityClass}')\n";
		$code .= "\t\t    ->map(fn(\$item) => \$this->{$mapperReference}->map('{$entityClass}', \$item));";

		$output->addMethod(
			name: $method->getName(),
			signature: $this->reflectUtils->getMethodDefinitionString( $method->class, $method->getName() ),
			implementation: $code,
		);
	}

	private function mapMethodPartToCode( ParsedMethodPart $part, Klist $params ): string {
		return match ( $part->operator ) {
			Operator::BETWEEN => "\t\t\$criteria->addCondition('$part->field', [\${$params->nextAndGet()}, \${$params->nextAndGet()}], \\Axpecto\\Storage\\Criteria\\Operator::BETWEEN, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
			Operator::IS_NULL,
			Operator::IS_NOT_NULL => "\t\t\$criteria->addCondition('$part->field', null, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
			default => "\t\t\$criteria->addCondition('$part->field', \${$params->nextAndGet()}, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
		};
	}
}
