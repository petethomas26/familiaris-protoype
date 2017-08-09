<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model {
	protected $table = 'log';

	protected $fillable = [
		'date',
		'type',
		'email',
		'member_name',
		'invite_no',
		'result'
	];

}