<?php

namespace App\Controllers\Knowledgebase;

use App\Controllers\Controller;

use App\Models\Member;

use App\Models\Person;

use App\Models\Parental;

use App\Models\Address;

use App\Models\AddressLink;

use App\Models\Education as ed;

use App\Models\EducationLink as edlink;

use App\Models\Partnership;

use App\Models\Pastime;

use App\Models\PastimeLink;

use App\Models\Employment;

use App\Models\EmploymentLink;

use Respect\Validation\Validator as v;



class KnowledgebaseController extends Controller {

	// Reverses format from d$m$y to y&m&d where $ and & are single characters such as '/' and '-'
	private function changeDateFormat($date, $separator1, $separator2) {
		if ($date !== null) {
			$d = explode($separator1, $date);
			if (count($d) == 3) {
				if ($d[1] === '00') {
					return '';
				} else {
					return $d[2] . $separator2 . $d[1] . $separator2 . $d[0];
				}
			} elseif (count($d) == 1) {
				return null;
			} else {
				$this->container->flash->addMessage('error', "Incorrect date format in: '".$date."'");
				return null;
			}
		} else {
			$this->container->flash->addMessage('error', "Null date given in 'KnowledgebaseController:changeDateFormat'");
			return null;
		}
	}

	// Is end date after start date?
	private function datesInOrder($startDate, $endDate) {
		return ($endDate > $endDate) ;
	}

	public function knowledgebase($request, $response) {
		// Get last (upto) 10 notices from notices db
		$count =  \App\Models\Notice::count();
		if ($count > 10) {
			$skip = $count -10;
			$notices = \App\Models\Notice::offset($skip)->limit(10)->get();
		} else {
			$notices = \App\Models\Notice::get();
		}

		return $this->container->view->render($response, 'Knowledgebase/knowledgebase.twig', compact('notices'));
	}

	public function getCreatePerson($request, $response, $args) {
		
		return $this->container->view->render($response, 'Knowledgebase/createPerson.twig', ['personId'=> $args['personId'], 'status'=>'normal', 'who'=>'other']);
	}


	public function getCreateMyPerson($request, $response, $args) {
		if (isset($_SESSION['person'])) {
			// Don't create Person if one already exists
			if ($_SESSION['person']) {
				$this->container->flash->addMessage('info', "You have already been added to the knowledgebase (click 'Get My Details' to see your data.)");
				return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
			} else {
				return $this->container->view->render($response, 'Knowledgebase/createPerson.twig', ['personId'=> '-1', 'status'=>'live', 'who'=>'me']);
			}

		} else {
			$this->container->flash->addMessage('info', "You have to be signed in to do that.");
			return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
		}
	}


	public function postCreatePerson($request, $response, $args) {
		
		$personId = $args['personId'];

		// Validate input
		$validation = $this->container->validator->validate($request, [
			'title' => v::optional(v::alpha()),
			'firstName' => v::notEmpty()->alpha(),
			'nickname' => v::optional(v::alpha()),
			'middleName' => v::optional(v::alpha()),
			'lastName' => v::notEmpty()->alpha(),
			'dateOfBirth' => v::date('d/m/Y'),
			'placeOfBirth' => v::alpha(),
			'dateOfDeath' => v::optional(v::date('d/m/Y')),
			'placeOfDeath' => v::optional(v::alpha())
		]);


		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Create Person: validation failed");
			// Go back to previous page
			if ($personId >= 0) {
				return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
			} else {
				return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
			}	
		};

		// Change date format from input format d/m/Y to db format Y-m-d
		$dob = $this->changeDateFormat($request->getParam('dateOfBirth'), '/', '-'); 

		// Date of death is optional
		if ($request->getParam('dateOfDeath') !== null) {
			$dod = $this->changeDateFormat($request->getParam('dateOfDeath'), '/', '-'); 
		} else {
			$dod = null;
		}

		// Unpack parameters, remove whitespace and convert to lowercase
		$title = $this->standardizeName($request->getParam('title'));
		$fname = $this->standardizeName($request->getParam('firstName'));
		$nickname = $this->standardizename($request->getParam('nickname'));
		$lname = $this->standardizeName($request->getParam('lastName'));
		$gen = $request->getParam('gender'); // taken from dropdown list
		$yob = date_parse_from_format('Y-m-d', $dob)['year'];
		$bplace = $this->standardizeName($request->getParam('placeOfBirth'));
		$who = $request->getParam('who');

		// Check whether Person already exists;
		$result = Person::findPeople($fname, $nickname, $lname, $yob, $bplace, $gen, 1.0);

		$resultStatus = $result['status'];

		if ($resultStatus === 'none' or $resultStatus === 'one-some' or $resultStatus === 'many-some') {
			// Create a new person record
			$person = Person::create([
				'title' => $title,
				'first_name' => $fname,
				'nickname' => $request->getParam('nickname'),
				'middle_name' => $request->getParam('middleName'),
				'last_name' => $lname,
				'date_of_birth' => $dob,
				'unsure_date_of_birth' => $request->getParam('unsureDateOfBirthFlag') !== null,
				'date_of_death' => $dod !== null,
				'unsure_date_of_death' => $request->getParam('unsureDateOfDeathFlag') !== null,
				'birth_location' => $request->getParam('placeOfBirth'),
				'unsure_place_of_birth' => $request->getParam('unsurePlaceOfBirthFlag')!== null,
				'death_location' => $request->getParam('placeOfDeath'),
				'unsure_place_of_death' => $request->getParam('unsurePlaceOfDeathFlag') !== null
			]);

			$personId = $person->id;
			$personName = $fname . ' ' . $lname;

			// Create a directory to hold photos of this person
			$personDirPath =  'images/' . $personId;

			if (!file_exists($personDirPath)) {

				if (!mkdir($personDirPath)) {
					$this->container->flash->addMessage('error', "Unable to create a photo folder for this person: " . $personDirPath);
					// Return to previous page
					if ($personId >= 0) {
						return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
					} else {
						return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
					};	
					
				};
				
			} else {
				$this->container->flash->addMessage('error', "Photo folder for person: " . $personDirPath . " already exists");
				// Return to previous page
				if ($personId >= 0) {
					return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
				} else {
					return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
				};	
			}

			// if this is a person for the current member, update their member record
			if ($args['who'] === 'me') {
				\App\Models\Member:: 
						where('memberId', '=', $args['memberId'])
						->update(['my_person_id', $personId]);
			}

			//Flash message
			$this->container->flash->addMessage('info', $personName . " has been added to the knowledgebase (with reference no: " . $personId . " and photo directory: " . $personDirPath . ")");
		} else if ($result['status'] === 'one-all') {
			// A person matching all criteria is already in knowledgebase; 
			// Is this person record already associated with a member?
			$person = $result['people'][0];
			$personId = $person['id'];
			$personName = $fname . ' ' . $lname;
			$member = \App\Models\Member::where('my_person_id', '=', $personId) -> first();
			if ($member == null) {
				// Person record not associated with a member; 
				// if this is a person for the current member, update their member record
				// and set SESSION variable
				$memberId = $_SESSION['member'];
				if ($request->getParam('who') === 'me') {
					\App\Models\Member:: 
							where('id', '=', $memberId)
							->update(['my_person_id'=> $personId]);
					$_SESSION['person'] = $personId;
				}
				$this->container->flash->addMessage('info', $personName . " has been added to the knowledgebase (with reference no: " . $personId . " and photo directory: " . $personDirPath . ")");
			} else {
				// Person record already associated with a member
				
				$memberId = $member['id'];
				if ($memberId === $_SESSION['member']) {
					if ($args['who'] == 'me') {
						// Person record associated with the current member and trying to create a person for them: 
						// No need to create a new person, just use the person record found
						$this->container->flash->addMessage('info', "A person named " . $personName . " is already in the knowledgebase (with reference no: " . $personId . ")");
					} else {
						// person record associated with current member but trying to create some other person: error
						$this->container->flash->addMessage('error', "A person named " . $personName . " is already in the knowledgebase (with reference no: " . $personId . ") but is associated with another member. This should not happen!");
						/******************************************
						* Inform administrators of this situation
						* *****************************************/
					}
				} else {
					// person record exists but is associated with a different member
					if ($args['who'] == 'me') {
						// Trying to create a person record for current member, but preson record already exists for different member: error
						$this->container->flash->addMessage('error', "A person named " . $personName . " is already in the knowledgebase (with reference no: " . $personId . ") but is associated with another member. This should not happen!");
						/******************************************
						* Inform administrators of this situation
						* *****************************************/
					} else {
						// No need to create a new person, just use the person record found
						$this->container->flash->addMessage('info', "A person named " . $personName . " is already in the knowledgebase (with reference no: " . $personId . ")");
					}
				}
			}
			
		} else {
			// $result['status'] === 'many-some'
			/******************************************
			* Inform administrators of this situation
			* *****************************************/
			$this->container->flash->addMessage('info', $result['message'] . ". Maybe this shouldn't happen!");
		}

		// Return to previous page (either 'knowledgebase' or 'person/n)'
		if ($personId >= 0) {
			// Required if creating a person record for the current member
			if ($who == 'me') {
				// Get current member and update Person reference
				$memberId = $_SESSION['member'];
				$member = Member::find($memberId);
				$member->my_person_id = $personId;
				//If this person corresponds to current memeber, save personId in SESSION variable
				$_SESSION['person'] = $personId;
			} 
			return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
		} else {
			return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
		};	

	}

	public function getPeople($request, $response) {
		$people = \App\Models\Person::get(); 
		
		return $this->container->view->render($response, 'Knowledgebase/people.twig', compact('people'));
	}

	// Simple find person
	public function findPerson($request, $response, $args) {
 		$currentYear = date('Y');

		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			'yearOfBirth' => v::optional(v::intVal()->between(0, $currentYear)),
			'placeOfBirth' => v::optional(v::alpha('-'))
		]);

		if ($validation->failed()) {
			
			$this->container->flash->addMessage('info', "Find " . $args['who'] . ": validation failed");
			return $response->withRedirect($this->container->router->pathFor($args['page'], ['personId'=>$args['personId']]));
		};
		
		$firstName =  $this->standardizeName($request->getParam('firstName'));
		$lastName =  $this->standardizeName($request->getParam('lastName'));
		$gender = "";
		$yearOfBirth = $request->getParam('yearOfBirth');
		$placeOfBirth =  $this->standardizeName($request->getParam('placeOfBirth'));

		$threshold = 1.0; // Similarity measure not currently implemented

		$result = Person::findPeople($firstName, null, $lastName, $yearOfBirth, $placeOfBirth, null, $threshold);

		$count = count($result['people']);

		if ($count == 0) {
			// return to previous page
			$this->container->flash->addMessage('info', $args['who'] . " not found in the knowledgebase.");
			return $response->withRedirect($this->container->router->pathFor($args['page'], ['personId'=>$args['personId']]));
		} elseif ($count == 1) {
			// go to person details page
			// Update previous page with new person details
			if ($args['who'] === 'father') {
				$person = \App\Models\Person::where('id', '=', $args['personId'])
            							->update(['father' => $result['people'][0]['id']]);
            } else if ($args['who'] === 'mother') {
            	$person = \App\Models\Person::where('id', '=', $args['personId'])
            							->update(['mother' => $result['people'][0]['id']]);
            } else if ($args['who'] === 'partner') {
            	// Update partner table
            	$person = \App\Models\Person::where('id', '=', $args['personId'])
            							->update(['partner' => $result['people'][0]['id']]);
            } else if ($args['who'] === 'sibling') {

            } else if ($args['who'] === 'child') {

            }

            $this->container->flash->addMessage('info', $args['who'] . " field updated");
			return $response->withRedirect($this->container->router->pathFor($args['page'], ['personId'=>$args['personId']]));
		} else {
			// go to choose person page
			$this->container->flash->addMessage('info', "More than one person matches your criteria.");
			return $response->withRedirect($this->container->router->pathFor($args['page'], [], $result));
			//return $this->container->view->render($response, 'Knowledgebase/choosePerson.twig', compact('people'));
		};

	}

	public function getFindPerson($request, $response) {
		$memberId = $_SESSION['member'];
		$favourites = \App\Models\Favourite::get()->
			where('member_id', '=', $memberId);
		$favouriteNames = [];
		foreach($favourites as $favourite) {
			$personId = $favourite['person_id'];
			$fName =  \App\Models\Person::where('id', '=', $personId)->value('first_name');
			$lName =  \App\Models\Person::where('id', '=', $personId)->value('last_name');
			$name = $fName . ' ' . $lName;
			$item = ["personId" => $personId, 'name'=> $name];
			$favouriteNames[] = $item;
			
		}
		
		return $this->container->view->render($response, 'Knowledgebase/findPerson.twig', compact('favouriteNames'));
	}


	public function postFindPerson($request, $response) {
		$currentYear = date('Y');
		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			'yearOfBirth' => v::optional(v::intVal()->between(0, $currentYear)),
			'placeOfBirth' => v::optional(v::alpha('-'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Find " . $args['who'] . ": validation failed");
			return $response->withRedirect($this->container->router->pathFor($args['page'], ['id'=>$args['id']]));
		};
		// Finds 'best' match with given criteria
		//  May not find an exact match
		$firstName =  $this->standardizeName($request->getParam('firstName'));
		$lastName =  $this->standardizeName($request->getParam('lastName'));
		$yearOfBirth = $request->getParam('yearOfBirth');
		$placeOfBirth =  $this->standardizeName($request->getParam('placeOfBirth'));

		$threshold = 1.0; // Similarity measure not currently implemented

		$result = Person::findPeople($firstName, null, $lastName, $yearOfBirth, $placeOfBirth, null, $threshold);

		$count = count($result['people']);
		
		if ($count == 0) {
			// return to person search page
			$this->container->flash->addMessage('info', $result['message']);
			return $response->withRedirect($this->container->router->pathFor('findPerson'));
		} elseif ($count == 1) {
			// go to person details page
			$this->container->flash->addMessage('info', $result['message']);
			return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$result['people'][0]['id']]));
		} else {
			// go to choose person page
			$people = $result['people'];
			//return $response->withRedirect($this->container->router->pathFor('choosePerson', [], ['people' => $people]));
			return $this->container->view->render($response, 'Knowledgebase/choosePerson.twig', compact('people'));
		};
		
	}

	public function getPersonId($request, $response) {
		// person taken from 'remembered' list
			$personName = $request->getParam('personName');
			$p = split('~', $personName);
			$personId = $p[0];
			return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	private function getSiblings($personId, $motherId, $fatherId) {
		/* Siblings include anyone who is a child of the person's parents
		* so includes half-brothers and sisters */
		
		$motherChildren = \App\Models\Parental::
								where('parent_id', '=', $motherId)
								->get();
		$fatherChildren = \App\Models\Parental::
								where('parent_id', '=', $fatherId)
								-> get();
		$siblings = [];
		if (count($motherChildren) > 0) {
			foreach ($motherChildren as $motherChild) {
				$motherChildId = $motherChild['child_id'];
				if ($motherChildId !== $personId) {
					$child = \App\Models\Person::find($motherChildId);
					$childName = $child->shortName();
					$siblings[] = ['id' => $motherChildId, 'name' => $childName];
				}
			}
			if (count($fatherChildren) > 0) {
				foreach ($fatherChildren as $fatherChild) {
					$found = false;
					$fatherChildId = $fatherChild['child_id'];
					if ($fatherChildId !== $personId) {
						foreach ($motherChildren as $motherChild) {
							if ($motherChild['child_id'] === $fatherchild['child_id'] ) {
								$found = true;
								break;
							}
						}
					}
					if (!$found) {
						$child = \App\Models\Person::find($fatherChildId);
						$childName = $child->shortName();
						$siblings[] =  [ 'id' => $fatherChildId, 'name' => $childName];
					}
				}
			}
		} else {
			if (count($fatherChildren) > 0) {
				foreach ($fatherChildren as $fatherChild) {
					$fatherChildId = $fatherChild['child_id'];
					if ($fatherChildId !== $personId) {
						$child = \App\Models\Person::find($fatherChildId);
						$childName = $child->shortName();
						$siblings[] =  ['id' => $fatherChildId, 'name' => $childName];
					}
				}
			}
		};
		
		return $siblings;
	}

	private function getChildren($personId) {
		// Obtain children of this person by extracting the children from parents table with 
		// this person as parent
		$parent_children = \App\Models\Parental::
									where('parent_id', '=', $personId)
									->get();
		$children =[];
		if (count($parent_children) > 0) {
			foreach($parent_children as $pc) {
				$childId = $pc['child_id'];
				$child = \App\Models\Person::find($childId);
				$childName = $child->shortName();
				$children[] = ['id' => $childId, 'name' => $childName];
			}
		};
		return $children;
	}



	private function getPartners($personId) {
		// If current person is current member then return all partnerships
		// otherwise return only public partnerships plus those private
		// partnerships that the member is involved in
		$memberId = $_SESSION['person']; // The personId of the current member
		
		if (isset($memberId) && ($personId == $memberId)) {
			$partnerships = \App\Models\Partnership::
									where('person_1', '=', $personId)
									->orWhere('person_2', '=', $personId)
									->get();
		} else {
			$partnerships = \App\Models\Partnership::
								where (function ($query) use ($personId) {
									$query->where (function($query) {
										$query->where('private', '=', false);
									})
									->where (function ($query) use ($personId) {
										$query->where('person_1', '=', $personId)
										->orWhere ('person_2', '=', $personId);
									});
								})
								->orWhere (function ($query) use ($personId, $memberId) {
									$query->where(function($query) {
										$query->where('private', '=', true);
									})
									->where (function ($query) use ($personId, $memberId) {
										$query->where (function ($query) use ($personId, $memberId) {
											$query->where('person_1', '=', $personId)
											->where('person_2', '=', $memberId);
										})
										->orWhere (function ($query) use ($personId, $memberId) {
											$query->where('person_1', '=', $memberId)
											->where ('person_2', '=', $personId);
										});
									});
								})
								->get();

		};
 
		$partners = [];
		
		foreach($partnerships as $partnership) {
			$partnerId = ($partnership['person_1'] === $personId) ? $partnership['person_2'] : $partnership['person_1'];
			$partner = \App\Models\Person::find($partnerId);
			$partnerName = $partner['first_name'] . ' ' . $partner['last_name'];

			$marriageDate = date_format(date_create($partnership['marriage_date']),"d-m-Y");

			if ($partnership['divorce_date'] === "0000-00-00") {
				$divorceDate = "";
			} else {
				$divorceDate = date_format(date_create($partnership['divorce_date']),"d-m-Y");
			};


			$partners[] =  ['id'=>$partnerId,
							'firstName' => $partner['first_name'],
							'nickname' => $partner['nickname'],
							'middleName' => $partner['middle_name'],
							'lastName' => $partner['last_name'],
							'dateOfBirth' => $partner['date_of_birth'],
							'partnerName' => $partnerName,
							'from'=>$marriageDate,
							'to'=>$divorceDate,
							'partnershipId'=>$partnership['id'],
							'private' => $partnership['private']
						];
			
		};
		return $partners;
	}

	private function getAddresses($personId) {
		if (isset($_SESSION['person']) && ($personId == $_SESSION['person'])) {
			$addressLinks = \App\Models\AddressLink::
								where('person_id', '=', $personId)
								->get();
		} else {
			$addressLinks = \App\Models\AddressLink::
									where('private', '=', false)
									->where('person_id', '=', $personId)
									->get();
		};
		
		$addresses = [];
		foreach ($addressLinks as $addressLink) {
			$address = \App\Models\Address::find($addressLink['address_id']);

			if ($address['from_date'] === "0000-00-00") {
				$fromdate = "";
			} else {
				$fromdate = date_format(date_create($address['from_date']),"d-m-Y");
			};
			if ($address['to_date'] === "0000-00-00") {
				$todate = "";
			} else {
				$todate = date_format(date_create($address['to_date']),"d-m-Y");
			};

			$addresses[] = [
					'id' => $address['id'],
					'houseNo' => $address['houseNo_Name'],
					'address1' => $address['address_1'],
					'address2' => $address['address_2'],
					'town' => $address['town'],
					'postcode' => $address['postcode'],
					'fromdate' => $fromdate,
					'todate'=> $todate,
					'addressLinkId'=> $addressLink['id'],
					'private' => $addressLink['private']
			];
		};
		
		return $addresses;
	}

	private function getNicknames($personId) {
		$nicknames = \App\Models\Nickname::
								where('person_id', '=', $personId)
								->get();
		return $nicknames;
	}

	private function getAlternativeLastNames($personId) {
		$lastNames = \App\Models\LastName::
								where('person_id', '=', $personId)
								->get();
		return $lastNames;
	}

	private function getCategoryItems($category, $personId) {
		//e.g. ('Education', 'EducationList', 32)
		$categoryLink = $category . 'Link';
		if (isset($_SESSION['person']) && ($personId == $_SESSION['person'])) {
			$links = $this->container[$categoryLink]->where('person_id', '=', $personId)
								->get();
		} else {
			$links = $this->container[$categoryLink]->where('private', '=', false)
						  ->where('person_id', '=', $personId)
						  ->get();
		}; 

		$category_id = lcfirst($category) . '_id';
		$categoryLinkId = lcfirst($category) . 'LinkId';
		$categoryItems = [];
		foreach ($links as $link) {
			$item = $this->container[$category]->find($link[$category_id]);
			// Need to change format of date fields (db format is "0000-00-00")
			if (isset($item['start_date'])) {
				$item['start_date'] = $this->changeDateFormat($item['start_date'], '-', '/');
			}
			if (isset($item['end_date'])) {
				$item['end_date'] = $this->changeDateFormat($item['end_date'], '-', '/');
			} 
			
			$categoryItems[] = [
				'category' => $item,
				$categoryLinkId => $link['id'],
				'private' => $link['private']
			];
		};

		return $categoryItems;
	}

	


	private function gatherPersonDetails($person) {
		// Gathers together all the details for a person with a given id

		$personDetails = [
			'name' => '',
			'nicknames' => [],
			'lastNames' => [],
			'motherName' =>'',
			'fatherName' => '',
			'currentPartnerName' => '',
			'siblings' => [],
			'children' => [],
			'partners' => [],
			'addresses' => [],
			'educations' => [],
			'medicals' => [],
			'pastimes' => [],
			'employments' => [],
			'politics' => []
		];
		

		$personDetails['name'] = $person->shortName();

		$personDetails['nicknames'] = $this->getNicknames($person['id']);

		$personDetails['lastNames'] = $this->getAlternativeLastNames($person['id']);
		
		$mother = \App\Models\Person::find($person['mother']);
		$personDetails['motherName'] = $mother['first_name'] . ' ' . $mother['last_name'];
		$father = \App\Models\Person::find($person['father']);
		$personDetails['fatherName'] = $father['first_name'] . ' ' . $father['last_name'];

		$currentPartner = \App\Models\Person::find($person['current_partner']);
		
		/* Siblings include anyone who is a child of the person's parents
		 * so includes half-brothers and sisters */
		$personDetails['siblings'] = $this->getsiblings($person['id'], $mother['id'], $father['id']);

		// Get children
		$personDetails['children'] = $this->getChildren($person['id']);

		// Get public partnerships
		$personDetails['partners'] = $this->getPartners($person['id']);

		//Is current partner in list of public partners?
		$personDetails['currentPartnerName'] = "";
		foreach($personDetails['partners'] as $partner) {
			if ($partner['id'] === $currentPartner['id']) {
				$personDetails['currentPartnerName'] = $currentPartner['first_name'] . ' ' . $currentPartner['last_name'];
			}
		};

		$personDetails['childOfMember'] = $person->isChild() and (($mother['id'] != 0  or $father['id'] != 0)) and (($_SESSION['person'] == $mother['id']) or ($_SESSION['person'] != $father['id']));

		// Get addresses lived at
		$personDetails['addresses'] = $this->getAddresses($person['id']);

		//Get education
		$personDetails['educations'] = $this->getCategoryItems('Education', $person['id']);      //getEducations($person['id']);

		//Get medical
		$personDetails['medicals'] = $this->getCategoryItems('Medical', $person['id']);			//getMedicals($person['id']);

		//Get pastimes
		$personDetails['pastimes'] = $this->getCategoryItems('Pastime', $person['id']);			//$this->getPastimes($person['id']);

		// Get career
		$personDetails['employments'] = $this->getCategoryItems('Employment', $person['id']);  	//$this->getEmployments($person['id']);

		// Get politics
		$personDetails['awards'] = $this->getCategoryItems('Award', $person['id']);				//$this->getAwards($person['id']);

		// Get politics
		$personDetails['services'] = $this->getCategoryItems('Service', $person['id']);			//$this->getServices($person['id']);

		// Get outputs
		$personDetails['outputs'] = $this->getCategoryItems('Output', $person['id']);			//$this->getOutputs($person['id']);

		// Get militaries
		$personDetails['militaries'] = $this->getCategoryItems('Military', $person['id']);

		// Get memories
		$personDetails['memories'] = $this->getCategoryItems('Memory', $person['id']);			//$this->getMemories($person['id']);

		// Get politics
		$personDetails['notes'] = $this->getCategoryItems('Note', $person['id']);				//$this->getNotes($person['id']);

		return $personDetails;

	}

	public function getPerson($request, $response, $args) {
		if (isset($_SESSION['member'])) {
			$person = \App\Models\Person::find($args['personId']);
			// Get personal history details
			$personDetails = $this->gatherPersonDetails($person);

			$editable = false;
			$memberId = $_SESSION['member'];
			if (isset($_SESSION['person'])) {
				$myPersonId = $_SESSION['person'];
			};
			/*
			return $this->container->view->render($response, 'Knowledgebase/person.twig', compact('person', 'name', 'editable', 'memberId', 'motherName', 'fatherName', 'currentPartnerName','partners', 'siblings', 'children', 'addresses', 'educations', 'pastimes', 'careers'));
			*/
			return $this->container->view->render($response, 'Knowledgebase/person.twig', compact('person', 'personDetails', 'memberId', 'editable', 'myPersonId'));
		} else {
			$this->container->flash->addMessage('info', "You have to be signed in to do that.");
			return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
		}
	}


	public function getMyPerson($request, $response, $args) {

		if (isset($_SESSION['person'])) {

			$myPersonId = $_SESSION['person'];

			if ($myPersonId === 0) {
				// A Person record not yet defined for this member
				$this->container->flash->addMessage('info', "You have not yet added yourself to the knowledgebase. To do so, click on 'Add new Person'");
				return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
			} else {
				$person = \App\Models\Person::find($myPersonId);
				$personDetails = $this->gatherPersonDetails($person);
				$editable=false;
				$memberId = $_SESSION['member'];
				$member = \App\Models\Member::find($memberId);
				$status = $member['status'];
				return $this->container->view->render($response, 'Knowledgebase/person.twig', compact('person', 'personDetails', 'editable', 'memberId', 'myPersonId', 'status'));
			}
		} else {
			$this->container->flash->addMessage('info', "No member is signed in. Cannot get deatials.");
			return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
		}
	}

	// Checks whether a person is in the knowledgebase
	public function isPerson($request, $response, $args) {

		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('membership'));
		};

		
		$fn =  $this->standardizeName($request->getParam('firstName'));
		$ln =  $this->standardizeName($request->getParam('lastName'));
		$dob = $request->getParam('dob');

		$first = \App\Models\Person::
				where(function($query) use ($ln, $dob, $fn) {
					$query -> where('last_name', '=', $ln)
					->where('date_of_birth', '=', $dob)
					->where('first_name', '=', $fn);
				})
				->orWhere (function ($query) use ($ln, $dob, $fn) {
					$query -> where('last_name', '=', $ln)
					->where('date_of_birth', '=', $dob)
					->orWhere('nickname', '=', $fn);
				})
				->first();
		
		if (isset($first)) {
			$message = "Yes, you appear to be in our knowledgebase; why not sign up?";
		} else {
			$message = "We cannot find you in our knowledgebase";
		};

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}


	public function getChoosePerson($request, $response, $args) {
		$people = $request->getParam('people');
		return $this->container->view->render($response, 'Knowledgebase/choosePerson.twig', compact('people'));
	}



	public function postUpdatePerson($request, $response, $args) {

		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'title' => v::optional(v::alpha()),
			'firstName' => v::notEmpty()->alpha('-'),
			'nickname' => v::optional(v::alpha('-')),
			'middleName' => v::optional(v::alpha('-')),
			'lastName' => v::notEmpty()->alpha('-'),
			'dateOfBirth' => v::date('d/m/Y'),
			'placeOfBirth' => v::optional(v::alpha('-')),
			'nationality' => v::optional(v::alpha('-')),
			'gender' => v::optional(v::alpha()),
			'natinsno' => V::optional(v::alNum()),
			'passportnNo' => v::optional(v::intVal()),
			'dateOfDeath' => v::optional(v::date('d/m/Y')),
			'placeOfDeath' => v::optional(v::alpha('-'))
		]);


		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Update Person: validation failed");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};

		// Birth year is essential; death year is optional
		$dob = $this->changeDateFormat($request->getParam('dateOfBirth'), '/', '-'); 
		$dod = $request->getParam('dateOfDeath');
		
		if ($dod === "") {
			$dod = null;
		} else {
			$dod = $this->changeDateFormat($dod, '/', '-'); 
		}

		// 'who' contains type of person and distinguishes between mother, father, partner, sibling, child and 'me'
		// Not required here
		$who = $request->getParam('who');

		// Trim string data
		// Update database

		$person = Person::
			where ('id', '=', $personId)
			->update([
			'title' => $this->standardizeName($request->getParam('title')),
			'first_name' => $this->standardizeName($request->getParam('firstName')),
			'nickname' =>  $this->standardizeName($request->getParam('nickname')),
			'middle_name' =>  $this->standardizeName($request->getParam('middleName')),
			'last_name' =>  $this->standardizeName($request->getParam('lastName')),
			'date_of_birth' => $dob,
			'unsure_date_of_birth' => $request->getParam('unsureDateOfBirthFlag'),
			'date_of_death' => $dod,
			'unsure_date_of_death' => $request->getParam('unsureDateOfDeathFlag'),
			'birth_location' =>  $this->standardizeName($request->getParam('placeOfBirth')),
			'death_location' =>  $this->standardizeName($request->getParam('placeOfDeath')),
			'gender' => $request->getParam('gender'),
			'nationality' =>  $this->standardizeName($request->getParam('nationality')),
			'nat_ins_no' => $request->getParam('natinsno'),
			'passport_no' => $request->getParam('passportNo'),
		]);

		// Construct a short name
		$personName = $request->getParam('firstName') . ' ' . $request->getParam('lastName');

		$this->container->flash->addMessage('info', "Person's identification details updated. ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));

	}

	//Add nickname
	public function addNickname($request, $response, $args) {

		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'nickname' => v::notEmpty()->alpha('-'),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Adding nickname: validation failed");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};

		//Is this nickname already in database?
		$nickname = $this->standardizeName($request->getParam('nickname'));
		$entry = \App\Models\Nickname:: where('person_id', '=', $personId)
							-> where('name', '=', $nickname)
							-> find(1);

		if ($entry != null ) {
			$this->container->flash->addMessage('info', "Nickname already in list of nicknames. ");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		} else {
			\App\Models\Nickname:: insert(
    				['person_id' => $personId, 'name' => $nickname]
				);
			// This is a bit crude; places last nickname into person record
			\App\Models\Person::
					where('id', '=', $personId)
            		->update(['nickname' => $nickname]);


			$this->container->flash->addMessage('info', "Nickname added to list of nicknames. ");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};
	}

	//Delete nickname
	public function deleteNickname($request, $response, $args) {
		
		$personId = $args['personId'];
		$nicknameId = $args['nicknameId'];

		\App\Models\Nickname::where('id', '=', $nicknameId) -> delete();

		// The following places any nickname from the nicknames table into the Person record
		$nicknames = \App\Models\Nickname::where('person_id', '=', $personId)->get();
		if ($nicknames != null ) {
			$nickname = $nicknames[0]['name'];
			\App\Models\Person::
					where('id', '=', $personId)
            		->update(['nickname' => $nickname]);
		} else {
			\App\Models\Person::
					where('id', '=', $personId)
            		->update(['nickname' => null]);
		};

		$this->container->flash->addMessage('info', "Person's nickname deleted. ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
	}

	//Set current last name 
	public function addCurrentLastName($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'currentLastName' => v::notEmpty()->alpha('-'),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Adding current last name: validation failed");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};

		$lastName =  $this->standardizeName($request->getParam('last_name'));

		//Is this name already in database in the Person's record?
		$entry = \App\Models\Person:: where('person_id', '=', $personId)
							-> where('last_name', '=', $lastName)
							-> find(1);

		if ($entry != null ) {
			$this->container->flash->addMessage('info', "Last name already set. ");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		} else {
			// Add to Person record (replaces anything already there)
			\App\Models\Person:: insert(
    				['person_id' => $personId, 'last_name' => $lastname]
				);

			$this->container->flash->addMessage('info', "Last name added to list of last names. ");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};

	}

	//Add a previous last name
	public function addPreviousLastName($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'previousLastName' => v::notEmpty()->alpha('-'),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Adding previous last name: validation failed");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		};

		$previousLastName =  $this->standardizeName($request->getParam('previousLastName'));

		//Is this name already in database? Either in person record or in list of previous lastnames
		$entry = \App\Models\Person:: where('person_id', '=', $personId)
							-> where('last_name', '=', $lastName)
							-> find(1);

		if ($entry !== null) {
			$this->container->flash->addMessage('info', "Name already exists as current last name. ");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
		} else {
			$entry = \App\Models\LastName:: where('person_id', '=', $personId)
							-> where('name', '=', $previousLastName)
							-> find(1);

			if ($entry != null ) {
				$this->container->flash->addMessage('info', "Name already in list of previous last names. ");
				return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
			} else {
				\App\Models\LastName:: insert(
	    				['person_id' => $personId, 'name' => $previousLastName]
					);

				$this->container->flash->addMessage('info', "Name added to list of previous last names. ");
				return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
			};
		};
	}

	// Remove a previous last name
	public function deletePreviousLastName($request, $response, $args) {
		$personId = $args['personId'];
		$lastNameId = $args['lastNameId'];

		 \App\Models\LastName::where('id', '=', $lastNameId) -> delete();

		$this->container->flash->addMessage('info', "Person's last name deleted. ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId' => $personId]));
	}

	// Get favourites from db
	public function getFavourites($request, $response, $args) {
		$memberId = $args['memberId'];
		$personId = $args['personId'];

		$favourites = \App\Models\Favourite::get()->
			where('member_id', '=', $memberId);

		if (count($favourites) > 0) {

			$people = [];
			$nicknames = [];
			foreach ($favourites as $fav) {
				$pId = $fav['person_id'];
				// Get person for this id
				$person = \App\Models\Person::find($pId);
				$person['date_of_birth'] = $this->changeDateFormat($person['date_of_birth'], '-', '/');
				$people[] = $person;
			}

			return $this->container->view->render($response, 'knowledgebase/favourites.twig', compact('people', 'personId'));
		} else {
			$this->container->flash->addMessage('info', "There are no people on your remembered list.");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
		}

	}


	// Add person id to list of people to be remembered by current user
	public function rememberPerson($request, $response, $args) {
		$memberId = $args['memberId'];
		$personId = $args['personId']; 
		
		$favourites = \App\Models\Favourite::get()->
			where('member_id', '=', $memberId)
			->where('person_id', '=', $personId);

		if ($favourites->count() == 0) {
			
			$row = \App\Models\Favourite::create([
				'member_id' => $memberId,
				'person_id' => $personId
			]);

			$message = "Person " . $personId . " has been added to your list of memorable people.";

		} else {
			$message = "Person " . $personId . " already in your (" . $memberId . ") remember list.";
		}


		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	// Remove person id from list of people to be remembered by current user
	public function forgetPerson($request, $response, $args) {
		$memberId = $args['memberId'];
		$personId = $args['personId'];

		
		$favourite = \App\Models\Favourite::
			where('member_id', '=', $memberId)
			->where('person_id', '=', $personId)
			->first();


		if (isset($favourite)) {
			$favourite->delete();

			$message = "Person forgotten";
		} else {
			$message = "This person is not on your remembered list.";
		}

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}




}