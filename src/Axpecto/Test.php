<?php

namespace Axpecto;

use Axpecto\Async\Async;

class Test {

	#[Async]
	public function callMethod() {
		file_put_contents( './test.txt', 'test' );
	}
}