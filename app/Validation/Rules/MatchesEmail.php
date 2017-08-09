<?php

namespace App\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class MatchesEmail extends AbstractRule {

	public function validate($input) {
		return $input === getenv('REG_EMAIL');
	}


}