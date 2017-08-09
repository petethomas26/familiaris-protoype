<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationLink extends Model {
	protected $table = 'education_links';
	
	protected $fillable = [
		'person_id',
		'education_id',
		'private'
	];


}