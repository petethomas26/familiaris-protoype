<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model {
	protected $table = 'education';

	protected $fillable = [
		'start_year',
		'end_year',
		'institution',
		'qualificaton',
		'subject'
	];

}