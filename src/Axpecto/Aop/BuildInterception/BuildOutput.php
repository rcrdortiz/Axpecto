<?php

namespace Axpecto\Aop\BuildInterception;

class BuildOutput {
	public function __construct(
		public readonly array $methods = [],
		public readonly array $useStatements = [],
		public readonly array $properties = [],
	) {
	}

	public function add(
		string $key,
		string $signature,
		string $implementation,
		array $properties = [],
		array $useStatements = [],
	): BuildOutput {
		$method = [ $key => $signature . "{ \n\t\t$implementation\n\t}\n" ];
		return new BuildOutput(
			methods:       array_merge( $this->methods, $method ),
			useStatements: array_merge( $this->useStatements, $useStatements ),
			properties:    array_merge( $this->properties, $properties ),
		);
	}

	public function hasOutput() {
		return $this->methods || $this->useStatements || $this->properties;
	}

	public function append(
		array $methods = [],
		array $useStatements = [],
		array $properties = [],
	): BuildOutput {
		return new BuildOutput(
			methods:       array_merge( $this->methods, $methods ),
			useStatements: array_merge( $this->useStatements, $useStatements ),
			properties:    array_merge( $this->properties, $properties ),
		);
	}
}