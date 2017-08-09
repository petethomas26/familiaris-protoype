<?php

namespace App\Validation\Rules;

use App\Models\Member;

use Respect\Validation\Rules\AbstractRule;

class EmailAvailable extends AbstractRule {

	public function validate($input) {
		return Member::where('email', $input)->count() === 0;
	}

}