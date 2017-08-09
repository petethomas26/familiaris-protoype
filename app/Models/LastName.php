<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LastName extends Model {
	protected $table = 'lastname';

	protected $fillable = [
		'person_id',
		'name',
	];

}