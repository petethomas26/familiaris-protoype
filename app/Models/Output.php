<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Output extends Model {
	protected $table = 'output';

	protected $fillable = [
		'year',
		'output',
		'collaborator',
		'description',
	];

}