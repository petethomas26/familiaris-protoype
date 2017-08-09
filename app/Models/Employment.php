<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employment extends Model {
	protected $table = 'employment';

	protected $fillable = [
		'job_title',
		'employer',
		'location',
		'start_year',
		'end_year',
	];

}