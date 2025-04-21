<?php

namespace Axpecto\Repository\Handler;

use Axpecto\Annotation\Annotation;
use Axpecto\Annotation\AnnotationReader;
use Axpecto\ClassBuilder\BuildHandler;
use Axpecto\ClassBuilder\BuildOutput;
use Axpecto\Code\MethodCodeGenerator;
use Axpecto\Collection\Klist;
use Axpecto\Collection\Kmap;
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
	private const MAPPER_PROP = 'mapper';
	private const STORAGE_PROP = 'storage';

	public function __construct(
		private ReflectionUtils $reflectionUtils,
		private MethodCodeGenerator $codeGenerator,
		private RepositoryMethodNameParser $methodNameParser,
		private EntityMetadataService $metadataService,
		private AnnotationReader $annotationReader,
	) {
	}

	/**
	 * @throws ReflectionException
	 * @throws Exception
	 */
	#[Override]
	public function intercept( Annotation $annotation, BuildOutput $buildOutput ): void {
		$repository = $this->ensureRepositoryAnnotation( $annotation );
		$entityAttr = $this->fetchEntityMetadata( $repository );

		// If the entity is not annotated, nothing to build
		// @TODO Maybe throw an exception here?
		if ( $entityAttr === null ) {
			return;
		}

		$this->reflectionUtils
			->getAbstractMethods( $repository->getAnnotatedClass() )
			->foreach( fn( ReflectionMethod $method ) => $this->buildRepositoryMethod( $method, $buildOutput, $repository, $entityAttr ) );
	}

	private function ensureRepositoryAnnotation( Annotation $ann ): Repository {
		if ( ! $ann instanceof Repository || $ann->getAnnotatedMethod() !== null ) {
			throw new InvalidArgumentException( 'Invalid @Repository annotation usage.' );
		}

		return $ann;
	}

	/**
	 * @throws ReflectionException
	 */
	private function fetchEntityMetadata( Repository $repo ): ?EntityAttribute {
		return $this->annotationReader
			->getClassAnnotations( $repo->entityClass, EntityAttribute::class )
			->firstOrNull();
	}

	/**
	 * Builds a single repository method: injects deps, validates params, generates code.
	 *
	 * @throws Exception
	 */
	private function buildRepositoryMethod(
		ReflectionMethod $method,
		BuildOutput $buildOutput,
		Repository $repository,
		EntityAttribute $entityAttr
	): void {
		$entityClass = $repository->entityClass;

		$this->injectDependencies( $buildOutput, $entityAttr );
		$fieldMap = $this->buildFieldToColumnMap( $entityClass );
		$parts    = $this->methodNameParser->parse( $method->getName() );

		$this->assertArgumentCountMatches( $method, $parts );

		$body      = $this->generateCriteriaBody( $method, $parts, $fieldMap, $entityClass );
		$signature = $this->codeGenerator->implementMethodSignature( $method->class, $method->getName() );

		$buildOutput->addMethod(
			name: $method->getName(),
			signature: $signature,
			implementation: $body,
		);
	}

	/**
	 * @throws Exception
	 */
	private function injectDependencies( BuildOutput $out, EntityAttribute $entityAttr ): void {
		$out->injectProperty( self::MAPPER_PROP, ArrayToEntityMapper::class );
		$out->injectProperty( self::STORAGE_PROP, $entityAttr->storage );
	}

	/**
	 * @throws ReflectionException
	 */
	private function buildFieldToColumnMap( string $entityClass ): Kmap {
		return $this->metadataService
			->getFields( $entityClass )
			->mapOf( fn( EntityField $f ) => [ $f->name => $f->persistenceMapping ] );
	}

	/**
	 * @throws Exception
	 */
	private function assertArgumentCountMatches( ReflectionMethod $method, Klist $parts ): void {
		$expected = $parts->reduce( fn( int $count, ParsedMethodPart $part ) => $count + $part->operator->argumentCount(), 0 );
		$actual   = count( $method->getParameters() );

		if ( $expected !== $actual ) {
			throw new Exception(
				sprintf(
					'%s declares %d args, but parsed conditions require %d.',
					$method->getName(),
					$actual,
					$expected
				)
			);
		}
	}

	/**
	 * Generates the method body that builds the Criteria and returns the mapped results.
	 *
	 * @throws Exception
	 */
	private function generateCriteriaBody(
		ReflectionMethod $method,
		Klist $parts,
		Kmap $fieldMap,
		string $entityClass
	): string {
		// Initialize criteria
		$code = "\$criteria = new \\Axpecto\\Storage\\Criteria\\Criteria();\n";

		// Extract parameter names in declaration order
		$paramNames = listFrom( $method->getParameters() )
			->map( fn( ReflectionParameter $p ) => $p->getName() );

		// For each part, map field name, then append condition code
		$code = $parts
			->filter( fn( ParsedMethodPart $p ) => (bool) $p->field )
			->map( fn( ParsedMethodPart $p ) => $p->copy( field: $fieldMap[ $p->field ] ?? throw new Exception( "Unknown field {$p->field}" ) ) )
			->reduce( fn( string $carry, ParsedMethodPart $part ) => $carry . $this->mapConditionCode( $part, $paramNames ), $code );

		// Append the final storage call
		$code .= "\t\treturn \$this->" . self::STORAGE_PROP .
		         "->findAllByCriteria(\$criteria, '$entityClass')\n";
		$code .= "\t\t    ->map(fn(\$item) => \$this->" .
		         self::MAPPER_PROP . "->map('$entityClass', \$item));";

		return $code;
	}

	private function mapConditionCode( ParsedMethodPart $part, Klist $params ): string {
		return match ( $part->operator ) {
			Operator::BETWEEN => "\t\t\$criteria->addCondition('{$part->field}', [\${$params->nextAndGet()}, \${$params->nextAndGet()}], \\Axpecto\\Storage\\Criteria\\Operator::BETWEEN, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
			Operator::IS_NULL,
			Operator::IS_NOT_NULL => "\t\t\$criteria->addCondition('{$part->field}', null, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
			default => "\t\t\$criteria->addCondition('{$part->field}', \${$params->nextAndGet()}, \\Axpecto\\Storage\\Criteria\\Operator::{$part->operator->name}, \\Axpecto\\Storage\\Criteria\\LogicOperator::{$part->logicOperator->name});\n",
		};
	}
}
