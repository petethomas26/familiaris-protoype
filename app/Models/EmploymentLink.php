<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentLink extends Model {
	protected $table = 'employment_links';
	
	protected $fillable = [
		'person_id',
		'employment_id',
		'private'
	];


}