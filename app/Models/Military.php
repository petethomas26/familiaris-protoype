<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Military extends Model {
	protected $table = 'military';

	protected $fillable = [
		'branch',
		'group',
		'rank',
		'awards',
		'start_date',
		'end_date',
		'description',
	];

}