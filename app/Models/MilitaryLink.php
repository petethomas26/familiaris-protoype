<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilitaryLink extends Model {
	protected $table = 'military_links';
	
	protected $fillable = [
		'person_id',
		'military_id',
		'private'
	];


}