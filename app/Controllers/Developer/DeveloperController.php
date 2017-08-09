<?php

namespace App\Controllers\Developer;

use App\Controllers\Controller;

use App\Models\Member;

use App\Models\Person;

use Respect\Validation\Validator as v;


class DeveloperController extends Controller {

	public function getCompareNames($request, $response, $args) {
		
		return $this->container->view->render($response, 'Developer/developer.twig');
	}


	public function postCompareNames($request, $response) {
		$name1 = $request->getParam('name1');
		$name2 = $request->getParam('name2');

		$similarity = Person::startSim($name1, $name2);

		$levSim = Person::levSim($name1, $name2);

		$simText = Person::textSim($name1, $name2);

		$metaphone = Person::metaphoneSim($name1, $name2);

		$similar = Person::nameSimilarity($name1, $name2, 1.0);
		$sim = ($similar) ? "True" : "False";
		return $this->container->view->render($response, 'Developer/developer.twig', compact('name1', 'name2', 'similarity', 'levSim', 'simText', 'metaphone', 'sim'));
	}

	
	
}