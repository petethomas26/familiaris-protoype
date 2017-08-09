<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partnership extends Model {
	protected $table = 'partnership';

	protected $fillable = [
		'person_1',
		'person_2',
		'marriage_date',
		'divorce_date',
		'private'
	];

	public function partnerName($personId) {
		
		$pid = ($this->person_1 === $personId) ? $this->person_2 : $this->person_1;
		$partner = \App\Models\Person::find($pid);
		return $partner->shortName();
	}

	

}