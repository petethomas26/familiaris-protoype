<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pastime extends Model {
	protected $table = 'pastime';
	protected $fillable = [
		'start_year',
		'end_year',
		'activity',
		'club',
		'description',
	];

}