<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model {
	protected $table = 'voting';

	protected $fillable = [
		'member_id',
		'opinion_id',
	];

}