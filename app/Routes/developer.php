<?php
/* Developer */

use App\Middleware\AuthMiddleware;

// Compare Names
$app->group('', function() {
	$this->get('/developer/getCompareNames', 'DeveloperController:getCompareNames')->setName('compareNames');

	$this->post('/developer/postCompareNames', 'DeveloperController:postCompareNames')->setName('postCompareNames');	
	
})->add(new AuthMiddleware($container));