<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardLink extends Model {
	protected $table = 'award_links';
	
	protected $fillable = [
		'person_id',
		'award_id',
		'private'
	];


}