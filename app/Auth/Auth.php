<?php

namespace App\Auth;

use App\Models\Member;

class Auth {

	// Has member been found (ie authenticated)
	public function check() {
		return isset($_SESSION['member']);
	}

	//Attempt to authenticate with given email and password
	//If authenticated, store member id in SESSION variable
	public function attempt($email, $password) {

		// Is email in db?
		$member = Member::where('email', $email)->first();

		// Member status 4 means account has been suspended
		if (!$member or $member['status'] === 4) {
			return false;
		}

		if (password_verify($password, $member->password)) {
			$_SESSION['member'] = $member->id;
			$_SESSION['person'] = $member->my_person_id;
			//dump($_SESSION['member'], $_SESSION['person']); die();
			return true;
		}

		return false;
	}


	// Return signed in (authenticated) member
	public function member() {
		if (isset($_SESSION['member'])) {
			return Member::find($_SESSION['member']);
		} else {
			return false;
		}
	}

	public function logout() {
		unset($_SESSION['member']);
		unset($_SESSION['person']);
	}



}