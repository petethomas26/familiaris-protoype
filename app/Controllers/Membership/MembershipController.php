<?php

namespace App\Controllers\Membership;

use App\Controllers\Controller;

use App\Models\Member;

use App\Models\Person;

use App\Models\Opinion;

use Respect\Validation\Validator as v;


class MembershipController extends Controller {
	private $minAge = 16; // A child is a person whose age is less than this value

	public function membership($request, $response) {
		$count =  \App\Models\Notice::count();
		
		if ($count > 10) {
			$skip = $count - 10;
			
			$notices = \App\Models\Notice::skip($skip)->take(10)->get();

		} else {
			$notices = \App\Models\Notice::get();
		}
		
		return $this->container->view->render($response, 'Membership/membership.twig', compact('notices'));
	}

	public function getMember($request, $response, $args) {
		$member = \App\Models\Member::find($args['id']); 
		
		return $this->container->view->render($response, 'Membership/member.twig', compact('member'));
	}

	public function getMembers($request, $response) {
		$members = \App\Models\Member::get(); 
		
		return $this->container->view->render($response, 'Membership/members.twig', compact('members'));
	}

	public function getFindMember($request, $response) {
		return $this->container->view->render($response, 'Membership/findMember.twig');
	}

	public function postFindMember($request, $response) {
		$memberName = $request->getParam('memberName');
		
		$threshold = 1.0; // Similarity measure not currently implemented

		$result = Member::findMembers($memberName, $threshold);

		$count = count($result['members']);

		// message comes from findMembers function
		$this->container->flash->addMessage('info', $result['message']);
		if ($count == 0) {
			// return to member search page
			return $response->withRedirect($this->container->router->pathFor('findMember'));
		} elseif ($count == 1) {
			// go to send message page
			
			$member = $result['members'][0];
			return $response->withRedirect($this->container->router->pathFor('sendMessage', [], $member));
		} else {
			// go to choose member page
			$member = $result['members'];
			return $response->withRedirect($this->container->router->pathFor('chooseMember', [], $result));
		};
		
	}

	private function getInvitation($toEmail, $memberId, $personId) {
		// Create invitation code and obtain a key to obscure email
		$invitationCode = Controller::getIdentifier();
		$key = Controller::getKey();

		// Create new db invitation record
		$invitation = new \App\Models\Invitation();
		$invitation->email = Controller::obscure($toEmail, $key); // obscured email
		$invitation->code = $invitationCode; // invitation code
		$invitation->vkey = $key; // obscure key
		$invitation->inviter = $memberId; // member who issued invitation
		$invitation->person_id = $personId; // record of person to whom invitation is being sent, if any

		// Save invitation record
		$invitation->save();

		return $invitationCode;
	}

	private function sendInvitationEmail($to, $body) {
		$from = "Familiaris@gmail.com";// Make this the email address

		$subject = "Invitation to join our family tree website";

		if ($this->mailer($from, $to, $subject, $body)) {
			$message ="Invitation sent";
		} else {
			$message = "Problem sending invitation; check email address and try again later.";
		}

		return $message;
	}


	public function invite($request, $response) {
		return $this->container->view->render($response, 'Membership/invite.twig');
	}

	public function postInvite($request, $response, $args) {


		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
			'confirm' => v::not(v::nullType())
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			
			return $response->withRedirect($this->container->router->pathFor('invite'));
		};

		$firstName =  standardizeName($request->getParam('firstName'));
		$lastName =  standardizeName($request->getParam('lastName'));
		$toEmail = $request->getParam('email');
		
		$memberId = $_SESSION['member'];
		$member = \App\Models\Member::find($memberId);
		$memberName = $member->member_name;

		// Obtain an invitation code for this member
		// Note: if there is a person record in the system for the invitee, record it in invitation record
		// Not yet implemented, so enter null
		$invitationCode = $this->getInvitation($toEmail, $member_id, null);

		// Create an invitation email
		$familiarisWebAddress = "Familiaris Web Address";
		$familiarisAdminEmailAddress = "Familiaris Email Address";

		$body = "<p>Dear " . $firstName . ",</p>" . $memberName . " has suggested that you might like to join Familiaris, a website containing our family tree. Our website contains information about people in our family (including our ancestors).</p><p>If you would like to know more, please take a look at our site: </p><p>" . $familiarisWebAddress . "</p><p>If you would like to join Familiaris, please sign up and enter the following invitation code (you can only view specific details about people if you have received an invitation and have signed up):</p><p>" . $invitationCode . " </p> <p>With best wishes</p><p>Familiaris</p><p>If you have any concerns about this email please email our administrators at " . $familirarisAdminEmailAddress . "</p>";

		$message = $this->sendInvitationEmail($toEmail, $body);

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

	public function sendMessage($request, $response) {
		$email = $request->getParam('email');
		
		return $this->container->view->render($response, 'Membership/sendMessage.twig', compact('email'));
	}

	public function postSendMessage($request, $response) {
		$to = $request->getParam('refno');
		$subject = $request->getParam('subject');
		$message = $request->getParam('message');
		$memberId = $_SESSION['member'];
		$from = \App\Models\Member::find($memberId);
		$member = \App\Models\Member::find($to);

		if (isset($member)) {

			if ($this->mailer($from, $to, $subject, $message)) {
				$message ="Message sent";
			} else {
				$message = "Problem sending invitation; check email address and try again later.";
			}

		} else {
			$message = "Unknown member reference.";
		}

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

	public function notice($request, $response) {
		
		return $this->container->view->render($response, 'Membership/notice.twig');
	}

	public function postNotice($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'heading' => v::notEmpty(),
			'notice' => v::notEmpty()
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields must not be empty.");
			
			return $response->withRedirect($this->container->router->pathFor('notice'));
		};

		$heading = $request->getParam('heading');
		$notice = $request->getParam('notice');
		$memberId = $_SESSION['member'];

		\App\Models\Notice::insert([
			'member_id' => $memberId,
			'heading' => $heading,
			'notice' => $notice
			]);
		
		
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

	

	public function getOpinion($request, $response) {
		// Only show opinions that have not yet closed
		$currentDate = date("Y-m-d");
		$opinions = \App\Models\Opinion::
								where('end_date', '>=', $currentDate)
								->get();
		
		
		// Need to omit questions that this member has already answered.
		$memberId = $_SESSION['member'];
		$votings = \App\Models\Vote::
								where('member_id', '=', $memberId)
								->get();
		foreach($votings as $voting) {
			$opinionId = $voting['opinion_id'];
			foreach($opinions as $key => $opinion) {
				
				if ($opinion['id'] === $opinionId) {
					
					unset($opinions[$key]);
					break;
				}
			}
		};


		return $this->container->view->render($response, 'Membership/opinion.twig', compact('opinions'));
	}

	public function postOpinion($request, $response) {
		$results = $request->getParams();
		
		foreach ($results as $result) {
			$res = explode(':', $result);
			if (count($res) !== 2) break;
			$index = $res[0];
			$answer = $res[1];
			$opinion = \App\Models\Opinion::find($index);
			
			if ($answer === "agree") {
				$votes_for = $opinion['votes_for'] + 1;
				\App\Models\Opinion::where('id', '=', $index)
									->update(['votes_for' => $votes_for]);
			} else {
				$votes_against = $opinion['votes_against'] + 1;
				\App\Models\Opinion::where('id', '=', $index)
									->update(['votes_against' => $votes_against]);
			};
				
		};

		$this->container->flash->addMessage('info', "Your opinion has been recorded, thank-you");
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

	public function getMyMembership($request, $response) {
		$memberId = $_SESSION['member'];
		$member = \App\Models\Member::find($memberId);
		$memberName = $member['member_name'];
		$personId = $member['my_person_id'];
		$dateJoined = $member['created_at'];
		$dateJoined = date_format($dateJoined, "d M Y");
		$statusNo = $member['status'];
		$email = $this->container->auth->member()->getEmail();
        $obscuredEmail = $this->obscureIt($email);
		switch ($statusNo)  {
			case 1: $status = "Ordinary member";
			break;
			case 2: $status = "Administrator";
			break;
			case 3: $status = "Developer";
			break;
			case 4: $status = "Membership suspended";
			break;
			default:
		};
		$memberDetails = [
					'memberName'=> $memberName,
					'memberId' => $memberId,
					'personId' => $personId,
					'dateJoined' => $dateJoined,
					'status' => $status,
					'email' => $email,
					'obscuredEmail' => $obscuredEmail
				];
		return $this->container->view->render($response, 'Membership/myMembership.twig', compact('memberDetails'));
	}

	public function postMyMembership($request, $response) {

		$this->container->flash->addMessage('info', "Your membership has been updated");
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

	public function getInviteChild($request, $response) {

		return $this->container->view->render($response, 'Membership/inviteChild.twig');
	}

	public function postInviteChild($request, $response) {
		/***************************************************************************
		*	Children under age of 16 cannot join Familiaris unless permission has
		*	been given by a parent. This function authorises familiaris to send an
		*	invitation to a child and allows the child to sign up.
		* **************************************************************************/

		// Validate data
		$currentYear = date('Y');
		$previousYear = $currentYear - $this->minAge;
		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			'yearOfBirth' => v::notEmpty()->intVal()->between($previousYear, $currentYear),
			'email' => v::noWhitespace()->notEmpty()->emailAvailable(),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			
			return $response->withRedirect($this->container->router->pathFor('inviteChild'));
		};

		// Obtain person record for the named person
		$firstName = $request->getParam('firstName');
		$lastName = $request->getParam('lastName');
		$yearOfBirth = $request->getParam('yearOfBirth');
		$email = $request->getParam('email');
		$result = Person::findPeople($firstName, null, $lastName, $yearOfBirth, null, null, 1.0);

		$people = $result['people'];

		if (count($people) === 0) {
			$this->container->flash->addMessage('info', "Could not find a child with these details. Please re-enter your details");
			return $response->withRedirect($this->container->router->pathFor('inviteChild'));
		} elseif (count($people) > 1) {
			$this->container->flash->addMessage('info', "Found more than one child with these details. Please re-enter your details");
			return $response->withRedirect($this->container->router->pathFor('inviteChild'));
		}

		$person = $people['0'];
		
		// Check that the named person is a child of the member; if not, error report
		if (!Person::isChildOfMember()) {
			$this->container->flash->addMessage('info', "This individual is not your child. Please re-enter child's details");
			return $response->withRedirect($this->container->router->pathFor('inviteChild'));
		}

		// Create an invitation; Save child's person record in invitation record
		$invitationCode = $this->getInvitation($toEmail, $member_id, $person['id']);

		// Send invitation to child's email (with slightly amended wording to normal)
		$familiarisWebAddress = "Familiaris Web Address";
		$familiarisAdminEmailAddress = "Familiaris Email Address";

		$body = "<p>Dear " . $firstName . ",</p>" . "your parent, " . $memberName . ", would like you to join Familiaris, the website containing our family tree. Our website contains information about people in our family (including our ancestors).</p><p>Our website address is: </p><p>" . $familiarisWebAddress . "</p><p>If you would like to join Familiaris, please go to the web site and sign up with the following invitation code:</p><p>" . $invitationCode . " </p> <p>With best wishes</p><p>Familiaris</p><p>If you have any concerns about this email please email our administrators at " . $familirarisAdminEmailAddress . "</p>";

		$message = $this->sendInvitationEmail($toEmail, $body);

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}
}