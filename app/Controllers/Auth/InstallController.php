<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;

use App\Models\Member;

use Respect\Validation\Validator as v;



class InstallController extends Controller {

	public function getInstall($request, $response){
		return $this->container->view->render($response, 'auth/install.twig');
	}


	public function postInstall($request, $response) {

		$validation = $this->container->validator->validate($request, [
			'email' => v::noWhitespace()->notEmpty()->matchesEmail(), 
			'memberName' => v::notEmpty()->alpha('-'),
			'password' => v::noWhitespace()->notEmpty(),
			'password_confirm' => v::noWhitespace()->notEmpty(),
			'registration' => v::noWhitespace()->notEmpty()->matchesRegistration()
		]);


		if ($validation->failed()) {
            // Log an invalid attempt to install
            $this->log("install", 
            			$request->getParam('email'),
            			$request->getParam('memberName'),
            			'none',
            			$request->getParam('registration'),
            			"fail");

			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('auth.install'));
		};

		

		// create a new member with administrator status
		$memberName = $request->getParam('memberName');
		$member = Member::create([
			'email' => $request->getParam('email'),
			'member_name' => $memberName,
			'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT, ['cost'=>10]),
			'status' => '2'
		]);

		// Log a successful install
		$this->log("install", 
        			$request->getParam('email'),
        			$request->getParam('memberName'),
        			$member['id'],
        			$request->getParam('registration'),
        			'ok');

		//Flash message
		$this->container->flash->addMessage('info', "You have been registered as an administrator with member name " . $request->getParam('memberName') ." and can invite others to join the website; but we need a few more details in order to add you to the family tree.");

		//Having successfully signed up, automatically get signed in
		$this->container->auth->attempt($member->email, $request->getParam('password'));

		// Create a message and save it to db
		$id = \App\Models\Notice::insertGetId([
			'member_id' => $member['id'],
			'heading' => "New Member",
			'notice' => "Welcome to " . $memberName . " (ref: " . $member['id'] . ") who has just registered as the first administrator."
			]);

		// create a person record
		return $response->withRedirect($this->container->router->pathFor('createMyPerson'));
	}
}