<?php

namespace App\Controllers;

use App\Models\Member;
use Slim\Views\Twig as View;

class HomeController extends Controller {

	public function index($request, $response) {

		//$user = User::where('email', 'alex@gmail.com')->first();
		/*
		User::create([
			'name'=> 'PeeWee Thos',
			'email' => 'pw@gmail.com',
			'password' => 'abc'
		]);
		*/
	
		//$this->container->flash->addMessage('error', 'Test flash message');
		
		return $this->container->view->render($response, 'home.twig');
	}

}