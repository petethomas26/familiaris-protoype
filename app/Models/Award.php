<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model {
	protected $table = 'award';

	protected $fillable = [
		'year',
		'award',
		'description',
	];

}