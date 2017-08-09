<?php

namespace App\Middleware;

class ValidationErrorsMiddleware extends Middleware {


	public function __invoke($request, $response, $next) {
		
		if (isset($_SESSION['errs'])) {
			$this->container->view->getEnvironment()->addGlobal('blah', $_SESSION['errs']);
			unset($_SESSION['errs']);
		}
		
		$response = $next($request, $response);

		return $response;
	}
}