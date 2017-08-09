<?php

namespace App\Controllers\Tree;

use App\Controllers\Controller;

use App\Models\Member;

use Respect\Validation\Validator as v;

class TreeController extends Controller {

	// Obtains data from db and converts it to JSON format required by tree
	protected function getJsonFromFile() {
		$people = \App\Models\Person::get(); 
		
		$data = [];

		foreach ($people as $person) {
			$name = $person['first_name'] . ' ' . $person['last_name'] . ' (' . $person['date_of_birth'] . ')';
			$parent = ($person['father']) ? $person['father'] : '#';
			$entry = '{ "id" : "' . $person['id'] . '", "parent" : "' . $parent . '", "text" : "' . $name . '" }, ' ;
			array_push($data, $entry);
		}

		return $data;
	}

	public function tree($request, $response) {
		return $this->container->view->render($response, 'Tree/tree.twig');
	}

	public function getFullTree($request, $response, $args) {
		$json = $this->getJsonFromFile();
	
		return $this->container->view->render($response, 'Tree/fullTree.twig', compact('json'));
	}

	public function postAncestorTree($request, $response, $args) {
		$reference = $request->getParam('reference');
		//Get the base person by reference or name
  		if(isset($reference)) {
  			$person = \App\Models\Person::find($reference); 
  			if (!isset($person)) {
  				$this->container->flash->addMessage('info', "A person with reference number " . $reference . " could not be found in knowledgebase.");
				return $response->withRedirect($this->container->router->pathFor('tree'));
  			};
  		} else {
			$validation = $this->container->validator->validate($request, [
				'firstName' => v::notEmpty()->alpha(),
				'lastName' => v::notEmpty()->alpha(),
			]);

			if ($validation->failed()) {
				dump("Validation failed", $validation);
				$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
				return $response->withRedirect($this->container->router->pathFor('tree'));
			};

			$person = findPersonByName($firstName, $lastName, $dob);

			if (!isset($person)) {
				$this->container->flash->addMessage('info', "Cannot find a person with those details.");
				return $response->withRedirect($this->container->router->pathFor('tree'));
			}
  		}


  		//initialise list of people
  		$people = [];
  		
  		//choose between ancestors or descendants (only ancestors at present)
  		$treeOption = $request->getParam("treeOptions");// => "ancestor"
  		
  		if ($treeOption == "maleAncestors") {
  			$people = $this->getAncestorList($person, 'father');
  			$gender = 'male';
  			return $this->container->view->render($response, 'Tree/lineTree.twig', compact('people','gender'));
  		} else if ($treeOption == "femaleAncestors") {
  			$people = $this->getAncestorList($person, 'mother');
  			$gender = 'female';
  			return $this->container->view->render($response, 'Tree/lineTree.twig', compact('people','gender'));
  		} else {
  			$tree = $this->ancestorTree($person['id']);
  			$name = $person->fullName();
  			return $this->container->view->render($response, 'Tree/ancestorTree.twig', compact('name', 'tree'));
  		}
		
	}

	public function getAncestorTree($request, $response, $args) {
		
		
		return $this->container->view->render($response, 'Tree/ancestorTree.twig');
	}

	public function getDescendantTree($request, $response, $args) {
		
		
		return $this->container->view->render($response, 'Tree/descendantTree.twig');
	}

	public function getUnlinkedPeople($request, $response, $args) {
		$people = \App\Models\Person::get(); 
		
		// Find all people without parent links
		$data = [];
		foreach ($people as $person) {
			if (($person['father'] !== null) and ($person['mother'] !== null) ) {
				array_push($data, $person);
			}
		}

		// Remove all people who are linked to from other people
		$unlinked = [];
		foreach ($data as $d) {
			$id = $d['id'];
			$found = false;
			foreach ($people as $person) {
				if (($person['father'] == $id) or ($person['mother'] == $id)) {
					$found = true;
					break;
				} 
			}
			if (!$found) {
				array_push($unlinked, $d);
			}
		}
		
		return $this->container->view->render($response, 'Tree/unlinkedPeople.twig', compact('unlinked'));
	}

	protected function getAncestorList($person, $gender) {
		$people = [];
  		do {
			array_push($people, $person);
			$ref = $person[$gender];
  			$person = \App\Models\Person::find($ref); 
  		} while (isset($person));

  		return $people;
	}

	protected function getDescendantList($person) {
		$people = [];
		array_push($people, $person);
		return $people;
	}

	public function parents($personId) {
		$parents = [];
		$person = \App\Models\Person::find($personId);
		$father = $person['father'];
		$mother = $person['mother'];
		$parents[0] = $father;
		$parents[1] = $mother;
		return $parents;
	}

	public function previousGeneration($thisGeneration) {
		$previousGeneration = [];
		foreach($thisGeneration as $personId) {
			$parents = $this->parents($personId);
			$previousGeneration[] = $parents[0];
			$previousGeneration[] = $parents[1];
		};
		return $previousGeneration;
	}

	public function isEmptyGeneration($thisGeneration) {
		foreach($thisGeneration as $personId) {
			if ($personId > 0) {
				return false;
			}
		};
		return true;
	}

	public function ancestorTree($personId) {
		$person = \App\Models\Person::find($personId);
		$tree[0] = [$personId];
		$generation[0] = $personId;
		$ancestors[0] = [$personId, $person->shortName()];
		$empty = false;
		while (!$empty) {
			$generation = $this->previousGeneration($generation);
			// Get name of each ancestor
			$people = [];
			
			foreach($generation as $pId) {
				if ($pId > 0) {
					$person = \App\Models\Person::find($pId);
					$people[] = [$pId, $person->shortName()];
				} else {
					$people[] = [-1,'?'];
				}
			}
			$empty = $this->isEmptyGeneration($generation);
			if (!$empty) {
				$tree[] = $generation;
				$ancestors[] = $people;
			}
		}
		
		return $ancestors;
	}

	public function getMyAncestorTree($request, $response, $args) {
		$personId = $args['personId'];
		$tree = $this->ancestorTree($personId);
  		$name = $args['personName'];
  		return $this->container->view->render($response, 'Tree/ancestorTree.twig', compact('name', 'tree'));
	}

}