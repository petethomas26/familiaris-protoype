<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoryLink extends Model {
	protected $table = 'memory_links';
	
	protected $fillable = [
		'person_id',
		'memory_id',
		'private'
	];


}