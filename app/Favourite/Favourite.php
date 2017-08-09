<?php

namespace App\Favoutite;

use App\Models\Person;

use App\Models\Member;

// Setting up and maintaining a list of favourites
class Favourite {

protected $favourites = [];


// Gets favourites from database table for a member
public function getFavourites($memberId) {
	$favourites = \App\Models\Favourite::
			where('member_id', '=', $args['member_id']);
			
}

// Check whether person with id is on favouites list
private function isFavourite($personId) {

}

// Adds a member to favouites list (db)
public function addPerson($personId) {
 	if (!isFavourite($personId)) {
 		// Add to list
 		// 
 	}
}

public function getFavouites() {
	if ($favourites is empty)
}

// Get favourites from db
	public function getFavourites($request, $response, $args) {
		

		return $response = withRedirect($this->container->router->pathFor('person'));
	}

	// Add person id to list of people to be remembered by current user
	public function rememberPerson($request, $response, $args) {
		$currentMemberId = $args['currentMemberId'];
		$rememberMemberId = $args['rememberMemberId']

		$this->container->flash->addMessage('info', "Person" . $rememberMemberId . "has been added to your list of memorable people.");
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}



}