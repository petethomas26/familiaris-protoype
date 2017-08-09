<?php

namespace App\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class MatchesRPreviousEmail extends AbstractRule {

	protected $email;

	public  function __construct($email) {
		$this->email = $email;
	}

	public function validate($input) {
		return $input === $this->email;
	}


}