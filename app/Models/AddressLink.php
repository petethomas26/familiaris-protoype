<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressLink extends Model {
	protected $table = 'address_links';
	
	protected $fillable = [
		'person_id',
		'address_id',
		'private'
	];


}