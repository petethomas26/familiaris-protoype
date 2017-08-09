<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model {
	protected $table = 'address';

	protected $fillable = [
		'houseNo_Name',
		'address_1',
		'address_2',
		'town',
		'postcode',
		'from_date',
		'to_date'
	];

}