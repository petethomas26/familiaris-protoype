<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opinion extends Model {
	protected $table = 'opinion';

	protected $fillable = [
		'statement',
		'end_date',
		'votes_threshold',
		'votes_for',
		'votes_against'
	];

}