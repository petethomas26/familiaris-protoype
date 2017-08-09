<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Query extends Model {
	protected $table = 'query';

	protected $fillable = [
		'from_member_id',
		'email',
		'subject',
		'query',
		'response',
		'related_query',
		'administrator',
		'status',
		'response_date',
	];

}