<?php

namespace App\Controllers;


class GuideController extends Controller {

	public function getGuide($request, $response) {
		return $this->container->view->render($response, 'guide.twig');
	}

	public function postGuide($request, $response) {

	}
}