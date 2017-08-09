<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteLink extends Model {
	protected $table = 'note_links';
	
	protected $fillable = [
		'person_id',
		'note_id',
		'private'
	];


}