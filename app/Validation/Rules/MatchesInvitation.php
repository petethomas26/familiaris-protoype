<?php

namespace App\Validation\Rules;

use App\Models\Invitation;

use App\Controllers\Controller;

use Respect\Validation\Rules\AbstractRule;

class MatchesInvitation extends AbstractRule {

	protected $email;

	public  function __construct($email) {
		$this->email = $email;
	}


	public function validate($input) {
		$invite = Invitation::where('code', '=', $input)->first();
		if ($invite === null) return false;
		$email = $invite['email'];		
		return $email === $this->email;
	}


}