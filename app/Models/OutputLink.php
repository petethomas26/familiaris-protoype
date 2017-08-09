<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutputLink extends Model {
	protected $table = 'output_links';
	
	protected $fillable = [
		'person_id',
		'output_id',
		'private'
	];


}