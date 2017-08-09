<?php

use Respect\Validation\Validator as v;


session_start();

// Bring in all dependencies
require __DIR__ . '/../vendor/autoload.php';

//Load the dotenv file containing config details (development or production)
$mode = file_get_contents(__DIR__ . '/mode.php');

$dotenv = (new \Dotenv\Dotenv(__DIR__, 'config/'.$mode.'.php'))->load();

// Create a Slim instance with settings
$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => getenv("ERROR_DISPLAY"),
		'db' => [
			'driver' => getenv("DB_DRIVER"),
			'host' => getenv("DB_HOST"),
			'port' => getenv("DB_PORT"),
			'database' =>getenv("DB_DATABASE"),
			'username' => getenv("DB_USERNAME"),
			'password' => getenv("DB_PASSWORD"),
			'charset' => getenv("DB_CHARSET"),
			'collation' => getenv("DB_COLLATION"),
			'prefix' => getenv("DB_PREFIX") 
		]
	]
]);

// Get the container
$container = $app->getContainer();

// Get capsule (Laravel mechanism for making Eloquent available)
$capsule = new \Illuminate\Database\Capsule\Manager;
// Make connection to database
$capsule->addConnection($container['settings']['db']);
// Make available globally so that we can use it with Models
$capsule->setAsGlobal();
// Boot Eloquent
$capsule->bootEloquent();

$container['mode'] = function($container) use($mode)  {
	return true;
};

$container['db'] = function($container) use($capsule) {
	return $capsule;
};

// Adding authentication
$container['auth'] = function($container) {
	return new \App\Auth\Auth;
};

$container['flash'] = function($container) {
	return new \Slim\Flash\Messages;
};


$container['view'] = function($container) use ($mode){
	// Create a Twig instance, say where we keeps our views and give options
	$view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
		'cache' => false,
		
	]);

	//Add extension to enable generation of urls to different routes within views
	$view->addExtension(new \Slim\Views\TwigExtension(
			$container->router,
			$container->request->getUri() //pass current uri
	));

	//$view->addExtension(new \Twig_Extension_Debug);

	$view->addExtension(new \App\Debug\DebugExtension);


	//When setting nav bar view, need auth. This mechanism avoids
	//multiple db accesses (i.e. data is pulled from db only once)
	$view->getEnvironment()->addGlobal('auth', [
		'check' => $container->auth->check(),
		'member' => $container->auth->member()
	]);

	//Incorporate flash messages into views
	$view->getEnvironment()->addGlobal('flash', $container->flash);

	$view->getEnvironment()->addGlobal('session', $_SESSION);

	$view->getEnvironment()->addGlobal('mode', $mode);

	return $view;
};

$container['validator'] = function($container) {
	return new App\Validation\Validator;
};

$container['mailer'] = function($container) {
	$mailer = new PHPMailer;
	$mailer->isSMTP();
	$mailer->SMTPDebug = 2; // Not in a production environment
	$mailer->Host = 'smtp.gmail.com';  // your email host, to test I use localhost and check emails using test mail server application (catches all  sent mails)
	$mailer->SMTPAuth = true;                 // I set false for localhost
	$mailer->SMTPSecure = 'ssl';              // set blank for localhost
	$mailer->Port = 465;                           // 25 for local host
	$mailer->Username = 'pete.thomas.26@gmail.com';    // I set sender email in my mailer call
	$mailer->Password = '$WestDerby$';
	$mailer->SMTPSecure = 'ssl';
	$mailer->isHTML(true);

	return $mailer;
};

$container['notFoundHandler'] = function($container) {
    return function($request, $response) use ($container)
    {
        return $container['view']->render($response->withStatus(404), 'error\404.twig');
    };    
};

/*************************************
* Controllers
* ***********************************/
$container['HomeController'] = function($container) {
	return new \App\Controllers\HomeController($container);
};

$container['AuthController'] = function($container) {
	return new \App\Controllers\Auth\AuthController($container);
};

$container['InstallController'] = function($container) {
	return new \App\Controllers\Auth\InstallController($container);
};

$container['PasswordController'] = function($container) {
	return new \App\Controllers\Auth\PasswordController($container);
};

$container['EmailController'] = function($container) {
	return new \App\Controllers\Auth\EmailController($container);
};


$container['GuideController'] = function($container) {
	return new \App\Controllers\GuideController($container);
};

$container['HelpController'] = function($container) {
	return new \App\Controllers\HelpController($container);
};

$container['AboutController'] = function($container) {
	return new \App\Controllers\AboutController($container);
};

$container['ContactController'] = function($container) {
	return new \App\Controllers\ContactController($container);
};

$container['NoticeController'] = function($container) {
	return new \App\Controllers\Notice\NoticeController($container);
};

$container['MembershipController'] = function($container) {
	return new \App\Controllers\Membership\MembershipController($container);
};

$container['KnowledgebaseController'] = function($container) {
	return new \App\Controllers\Knowledgebase\KnowledgebaseController($container);
};

$container['TreeController'] = function($container) {
	return new \App\Controllers\Tree\TreeController($container);
};

$container['AdminController'] = function($container) {
	return new \App\Controllers\Administration\AdminController($container);
};

$container['PhotoController'] = function($container) {
	return new \App\Controllers\Photo\PhotoController($container);
};

$container['PartnershipController'] = function($container) {
	return new \App\Controllers\Partnership\PartnershipController($container);
};

$container['AddressController'] = function($container) {
	return new \App\Controllers\Address\AddressController($container);
};

$container['EducationController'] = function($container) {
	return new \App\Controllers\Education\EducationController($container);
};

$container['MedicalController'] = function($container) {
	return new \App\Controllers\Medical\MedicalController($container);
};

$container['ServiceController'] = function($container) {
	return new \App\Controllers\Service\ServiceController($container);
};

$container['EmploymentController'] = function($container) {
	return new \App\Controllers\Employment\EmploymentController($container);
};

$container['PoliticalController'] = function($container) {
	return new \App\Controllers\Political\PoliticalController($container);
};

$container['PastimeController'] = function($container) {
	return new \App\Controllers\Pastime\PastimeController($container);
};

$container['AwardController'] = function($container) {
	return new \App\Controllers\Award\AwardController($container);
};

$container['OutputController'] = function($container) {
	return new \App\Controllers\Output\OutputController($container);
};

$container['MemoryController'] = function($container) {
	return new \App\Controllers\Memory\MemoryController($container);
};

$container['MilitaryController'] = function($container) {
	return new \App\Controllers\Military\MilitaryController($container);
};

$container['NoteController'] = function($container) {
	return new \App\Controllers\Note\NoteController($container);
};


$container['VoteController'] = function($container) {
	return new \App\Controllers\Vote\VoteController($container);
};

$container['DeveloperController'] = function($container) {
	return new \App\Controllers\Developer\DeveloperController($container);
};


/********************************************
* Models
* *******************************************/
$container['Member'] = function($container) {
	return new \App\Models\Member;
};

$container['Person'] = function($container) {
	return new \App\Models\Person;
};

$container['Parnt'] = function($container) {
	return new \App\Models\Parnt;
};

$container['Address'] = function($container) {
	return new \App\Models\Address;
};

$container['AddressLink'] = function($container) {
	return new \App\Models\AddressLink;
};

$container['Education'] = function($container) {
	return new \App\Models\Education;
};

$container['EducationLink'] = function($container) {
	return new \App\Models\EducationLink;
};

$container['Opinion'] = function($container) {
	return new \App\Models\Opinion;
};

$container['Pastime'] = function($container) {
	return new \App\Models\Pastime;
};

$container['PastimeLink'] = function($container) {
	return new \App\Models\PastimeLink;
};

$container['Employment'] = function($container) {
	return new \App\Models\Employment;
};

$container['EmploymentLink'] = function($container) {
	return new \App\Models\EmploymentLink;
};

$container['Medical'] = function($container) {
	return new \App\Models\Medical;
};

$container['MedicalLink'] = function($container) {
	return new \App\Models\MedicalLink;
};

$container['Award'] = function($container) {
	return new \App\Models\Award;
};

$container['AwardLink'] = function($container) {
	return new \App\Models\AwardLink;
};

$container['Service'] = function($container) {
	return new \App\Models\Service;
};

$container['ServiceLink'] = function($container) {
	return new \App\Models\ServiceLink;
};

$container['Political'] = function($container) {
	return new \App\Models\Political;
};

$container['PoliticalLink'] = function($container) {
	return new \App\Models\PoliticalLink;
};

$container['Output'] = function($container) {
	return new \App\Models\Output;
};

$container['OutputLink'] = function($container) {
	return new \App\Models\OutputLink;
};

$container['Memory'] = function($container) {
	return new \App\Models\Memory;
};

$container['MemoryLink'] = function($container) {
	return new \App\Models\MemoryLink;
};

$container['Military'] = function($container) {
	return new \App\Models\Military;
};

$container['MilitaryLink'] = function($container) {
	return new \App\Models\MilitaryLink;
};

$container['Vote'] = function($container) {
	return new \App\Models\Vote;
};

$container['Note'] = function($container) {
	return new \App\Models\Note;
};

$container['NoteLink'] = function($container) {
	return new \App\Models\NoteLink;
};

$container['LastName'] = function($container) {
	return new \App\Models\LastName;
};

$container['Nickname'] = function($container) {
	return new \App\Models\Nickname;
};


/*********************************************
* Adding cross site request forgery guard
**********************************************/
$container['csrf'] = function($container) {
	$csrf = new \Slim\Csrf\Guard;
	$csrf->setFailureCallable(function ($request, $response, $next) use ($container) {
		   return $container['view']->render($response->withStatus(400), 'Error/400.twig');
		});
	return $csrf;
};

// Turn on CSRF
$app->add($container->csrf);


/*********************************
* Middleware
* ********************************/
$app->add(new \App\Middleware\ValidationErrorsMiddleware($container));
$app->add(new \App\Middleware\OldInputMiddleware($container));
$app->add(new \App\Middleware\CsrfViewMiddleware($container));


/********************************************
* Enables new validation rules to be added
********************************************/
v::with('App\\Validation\\Rules\\');

/*********************************
* Routes
* *******************************/
require __DIR__ . '/../app/routes.php';

require __DIR__ . '/../app/Routes/about.php';
require __DIR__ . '/../app/Routes/contact.php';
require __DIR__ . '/../app/Routes/developer.php';
require __DIR__ . '/../app/Routes/guide.php';
require __DIR__ . '/../app/Routes/overview.php';

