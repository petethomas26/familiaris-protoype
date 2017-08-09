<?php

namespace App\Models; 

use Illuminate\Database\Eloquent\Model;

class Photo extends Model {
	protected $table = 'photo';

	protected $fillable = [
		'description',
		'person_id',
		'image_name'
	];

	public function getImageName($person_id) {
		$row = \App\Models\Photo::
					where('person_id', '=', $personId)
					-> first();

		$image = $row[‘image’];
		header('content-type : image/jpeg');
		return $image;
	}

}