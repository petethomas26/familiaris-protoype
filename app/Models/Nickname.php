<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nickname extends Model {
	protected $table = 'nickname';

	protected $fillable = [
		'person_id',
		'name',
	];

}