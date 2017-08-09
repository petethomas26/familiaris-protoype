<?php

namespace App\Controllers\Auth;

use App\Models\Member;
use App\Controllers\Controller;
use Respect\Validation\Validator as v;

class PasswordController extends Controller {

	public function getChangePassword($request, $response) {
		return $this->container->view->render($response, 'auth/password/change.twig');
	}

	public function postChangePassword($request, $response) {
		$validation = $this->container->validator->validate($request, [
			'password_old' => v::noWhitespace()->notEmpty()->matchesPassword($this->container->auth->member()->password),
			'password' => v::noWhitespace()->notEmpty()
		]);

		if ($validation->failed()) {
			return $response->withRedirect($this->container->router->pathFor('auth.password.change'));
		}

		$this->container->auth->member()->setPassword($request->getParam('password'));

		$this->container->flash->addMessage('info', 'Your password has been changed.');

		return $response->withRedirect($this->container->router->pathFor('home'));
	}

}