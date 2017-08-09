<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Controllers\Controller;

class Member extends Model {
	protected $table = 'member';

	protected $fillable = [
		'my_person_id',
		'member_name',
		'email',
		'previous_email',
		'password',
		'status'
	];

	public function setPassword($password) {
		$this->update([
			'password' => password_hash($password, PASSWORD_DEFAULT)
		]);
	}

	public function setEmail($email){
		$this->update([
			'email' => $email
		]);
	}

	public function setPreviousEmail($email){
		$this->update([
			'previous_email' => $email
		]);
	}

	public function getEmail() {
		return $this->email;
	}

	public function getPreviousEmail() {
		return $this->previous_email;
	}

	public function getName() {
		return $this->member_name;
	}

	public function getPersonId() {
		return $this->my_person_id;
	}

	/*****************************************************************************
	 * Finds as many members in knowledgebase that match the given member name
	 * The initial algorithm looks for exact matches on names
	 * Assumes parameters have been validated.
	 *
	 * To be implemented:
	 *   Partial matches with $firstName and $lastName are supported: a similarity 
	 *     value is returned with matches ordered on similarity
	 *   Only those people with a similarity measure greater than 'threshold' 
	 *     are returned.
	 *     
	 * A similarity measure (including threshold) is a value in the range [0,1]
	 * 
	 ****************************************************************************/
	public function findMembers($name, $threshold) {
		// Initialise $results
		$result['members'] = null;
		$result['similarity'] = 0.0;
		$result['message'] = '';

		// Initialise local vars
		$similarity = 0.0;

		// Find all members in knowledgebase with member_name matching $name
		$members = \App\Models\Member::
				where('member_name', '=', $name)
				->get();

		
		
		// Change to array for processing
		$result['members'] = $members->toArray();

		$noMembers = count($result['members']);

		//dump($noMembers); die();

		if (empty($result)) {
			$result['similarity'] = 0.0;
			
			return $result;
		}
		

		// One or more matching results found
		
		if ($noMembers == 0) {
			$result['message'] = "No-one with that name found in membership list. Please try another name.";
		}
		elseif ($noMembers == 1) {
				$result['message'] = "A member matching your criteria has been found";
		} else {
				$result['message'] = $noMembers . " members match your criteria";
		};

		return $result;

	}


}
