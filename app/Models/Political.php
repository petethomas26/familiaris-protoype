<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Political extends Model {
	protected $table = 'political';

	protected $fillable = [
		'start_date',
		'end_date',
		'activity',
		'description',
		'unsure_start_date',
		'unsure_end_date'
		
	];

}