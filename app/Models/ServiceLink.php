<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceLink extends Model {
	protected $table = 'service_links';

	protected $fillable = [
		'person_id',
		'service_id',
		'private'
	];

}