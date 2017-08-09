<?php

namespace App\Controllers;

use App\Models\Member;

use Respect\Validation\Validator as v;

class HelpController extends Controller {

	public function getHelp($request, $response) {
		return $this->container->view->render($response, 'help.twig');
	}

	public function postHelp($request, $response) {

	}
}