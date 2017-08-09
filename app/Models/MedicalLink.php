<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalLink extends Model {
	protected $table = 'medical_links';
	
	protected $fillable = [
		'person_id',
		'medical_id',
		'private'
	];


}