<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parental extends Model {
	protected $table = 'parent';

	protected $fillable = [
		'parent_id',
		'child_id'
	];

}