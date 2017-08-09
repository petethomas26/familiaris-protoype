<?php

namespace App\Controllers;

class Controller {

	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}

	protected $charset = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

	protected function obscureIt($value)  {
		$obscured = null;
		$identifier = md5(uniqid(true));
		$key = str_shuffle($this->charset);
		for ($i=0; $i<strlen($value); $i++) {
			$obscured .= $key[strpos($this->charset, $value[$i])];
		}
		$output = <<<EOT
		<span id="{$identifier}" value="{$obscured}">[email protected]</span>
		<script>
			(function(k,o) {
				var c=k.split('').sort().join('');
				var r = '';
				for (var i=0; i<o.length; i++) {
					r += c.charAt(k.indexOf(o.charAt(i)));
				};
				document.getElementById('{$identifier}').innerText = r;
			}) ('{$key}', '{$obscured}');
		</script>
EOT;
		return trim(preg_replace('/\s+/', ' ', $output));

	}

	

// Convert to a standard format for a name
// All lowercase with initial letter (or one that follows a hyphen) capitalized
	protected function standardizeName($name) {
		$name = trim($name);
		$name = strtolower($name);
		$parts = explode('-', $name);
		$name = '';
		foreach($parts as $part) {
			$part = ucfirst($part);
			$name = $name . $part;
		};
		return $name;
	}

	/*****************************************************************
	* Searches for a person in the knowledgebase with the given
	* first name, last name and date of birth where the given
	* first name can match with either a first_name or a nickname.
	* Caution: there may be more than one person that meets the
	* criteria but only one is returned.
	* If no-one is found, a null value is returned
	*****************************************************************/
	protected function findPersonByName($firstName, $lastName, $dob) {
		
		$first = \App\Models\Person::
				where(function($query, $lastName,$dob,$firstName) {
					$query -> where('last_name', '=', $lastName)
					->where('date_of_birth', '=', $dob)
					->where('first_name', '=', $first_name);
				})
				->orWhere (function ($query, $lastName, $dob, $firstName) {
					$query -> where('last_name', '=', $lastName)
					->where('date_of_birth', '=', $dob)
					->orWhere('nickname', '=', $firstName);
				})
				->first();

		return $first;
	}

	/****************************************************************************************
	*	The purpose of this routine is to store data about (log) significant events.
	*	The events are:
	*		attempt to sign up (type=)
	*		attempt to sign in (type=)
	*		attempt to change password (type=)
	*		attempt to change email (type=)
	*	The result of an attempt is either success, denoted "ok", or "fail"
	*	The data consists of:
	*		'date': a timestamp of when the event occurred
	*		'type': the type of event ("register", "login", "changePassword", "changeEmail")
	*		'result': the result of the attempt, either "ok" or "fail"
	*		'member_name': the name given by the user who actioned the event
	*		'memberId': the id of the member who actioned the event, if known
	*		'email': the email address given by the user who actioned the event
	*		'invite_no': the invitation number given by the user who actioned the event
	*		
	* *************************************************************************************/
	protected function log($type, $email, $memberName, $memberNo, $inviteNo, $result) {
		
		$date = date_create()->format('Y-m-d H:i:s');
		
		\App\Models\Log::create([
			'date' => $date,
			'type' => $type,
			'email' => $email,
			'member_name' => $memberName,
			'member_no' => $memberNo,
			'invite_no' => $inviteNo,
			'result' => $result
		]);
	}

	// $to is an array with possibly multiple email addresses
	protected function mailer($from, $to, $subject, $body) {

		$this->container->mailer->From = $from;
		$this->container->mailer->FromName = 'Familiaris';
		//$m->addReplyTo('reply@gmail.com', 'Reply address');
		foreach ($to as $address) {
			
			$this->container->mailer->addAddress($address, 'Familiaris');
		};

		//$m->addCC('pete.thomas.26@gmail.com', 'Pete Thomas');
		//$m->addBCC('pete.thomas.26@gmail.com', 'Pete Thomas');

		$this->container->mailer->Subject = $subject;
		$this->container->mailer->Body = $body;
		$this->container->mailer->AltBody = strip_tags($body);

		if ($this->container->mailer->send()) {
			return true;
		} else {
			//dump($this->container->mailer->ErrorInfo);die(); 
			return false;
		}
	}
	
}