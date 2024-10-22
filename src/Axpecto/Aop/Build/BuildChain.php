<?php

namespace Axpecto\Aop\Build;

use Axpecto\Aop\Annotation;
use Axpecto\Collection\Concrete\Klist;

/**
 * Class BuildChain
 *
 * This class represents a chain of responsibility for handling build processes
 * involving annotations. It processes annotations in sequence, passing control
 * to the associated builder for each annotation, and ensures that the output is
 * merged rather than replaced after each step in the chain.
 *
 * Key functionality includes:
 * - Managing the flow of annotations through a chain.
 * - Allowing each annotation's builder to modify or append to the output.
 * - Ensuring that the output is merged as the process proceeds.
 *
 * @package Axpecto\Aop\Build
 */
class BuildChain {

	/**
	 * BuildChain constructor.
	 *
	 * Initializes the build chain with annotations, the target class, the target method (if any),
	 * and an initial build output.
	 *
	 * @param Klist<Annotation> $annotations List of annotations to process.
	 * @param BuildOutput       $output      The initial output to be modified or extended during processing.
	 */
	public function __construct(
		public readonly Klist $annotations,
		public readonly BuildOutput $output,
	) {
	}

	/**
	 * Proceed through the build chain, processing the annotations.
	 *
	 * This method processes the next annotation in the chain by calling the builder associated with it.
	 * It merges the current output with the new output produced during the process.
	 *
	 * @return BuildOutput The merged build output after processing the annotations.
	 */
	public function proceed(): BuildOutput {
		// Retrieve the next annotation in the list.
		$annotation = $this->annotations->nextElement();

		// If the next element is not an annotation, return the current output.
		if ( ! $annotation instanceof Annotation ) {
			return $this->output;
		}

		// If the current annotation doesn't have a handler, proceed with the method call.
		$builder = $annotation->getBuilder();
		if ( ! $builder ) {
			return $this->proceed();
		}

		// Call the intercept method on the annotation's handler, allowing it to modify behavior.
		return $builder->intercept( $this, $annotation, $this->output );
	}
}
