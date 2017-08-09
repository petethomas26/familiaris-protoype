<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PastimeLink extends Model {
	protected $table = 'pastime_links';
	
	protected $fillable = [
		'person_id',
		'pastime_id',
		'private'
	];


}