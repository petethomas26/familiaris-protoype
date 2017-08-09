<?php

namespace App\Validation\Rules;

use App\Models\Member;

use Respect\Validation\Rules\AbstractRule;

class MatchesPassword extends AbstractRule {

	protected $password;

	public  function __construct($password) {
		$this->password = $password;
	}

	public function validate($input) {
		return $input === $this->password;
		//return password_verify($input, $this->password);
	}

}