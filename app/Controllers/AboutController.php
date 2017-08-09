<?php

namespace App\Controllers;

use App\Models\Member;

use Respect\Validation\Validator as v;

class AboutController extends Controller {

	public function getAbout($request, $response) {
		return $this->container->view->render($response, 'about.twig');
	}

	public function postAbout($request, $response) {

	}
}