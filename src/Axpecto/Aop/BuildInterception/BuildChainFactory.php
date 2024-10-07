<?php

namespace Axpecto\Aop\BuildInterception;

use Axpecto\Collection\Concrete\Klist;

class BuildChainFactory {

	public function get(
		Klist $annotations,
		string $class,
		?string $method = null,
		BuildOutput $output = new BuildOutput(),
	): BuildChain {
		return new BuildChain( $annotations, $class, $method, $output );
	}
}