<?php

namespace App\Controllers;

use App\Models\Member;

use Respect\Validation\Validator as v;

class ContactController extends Controller {

	public function getContact($request, $response) {
		return $this->container->view->render($response, 'contact.twig');
	}

	public function postContact($request, $response) {

	}
}