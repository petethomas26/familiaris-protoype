<?php

namespace App\Controllers\Auth;

use App\Models\Member;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

class EmailController extends Controller {

	public function getChangeEmail($request, $response) {
		return $this->container->view->render($response, 'auth/email/change.twig');
	}

	public function postChangeEmail($request, $response) {

		$validation = $this->container->validator->validate($request, [
			'oldEmail' => v::noWhitespace()->notEmpty()->matchesPreviousEmail($this->container->auth->member()->email),
			'newEmail' => v::noWhitespace()->notEmpty(),
			'password' => v::notEmpty()->matchesPassword($this->container->auth->member()->password),
		]);

		if ($validation->failed()) {
			// Log failure
			$memberId = $_SESSION['member'];
			$email = $request->getParam('oldEmail');
			$this->log('changeEmail', 
	        			$email,
	        			'none',
	        			$memberId,
	        			'none',
	        			'fail');
			return $response->withRedirect($this->container->router->pathFor('auth.email.change'));
		}

		// Save current email
		$this->container->auth->member()->setPreviousEmail($request->getParam('oldEmail');

		// Store new email
		$this->container->auth->member()->setEmail($request->getParam('newEmail');

		// Log change of email
		$memberId = $_SESSION['member'];
		$email = $request->getParam('oldEmail');
		$this->log('changeEmail', 
        			$email,
        			'none',
        			$memberId,
        			'none',
        			'ok');

		$this->container->flash->addMessage('info', 'Your email has been changed.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

}