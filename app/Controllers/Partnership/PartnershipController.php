<?php

namespace App\Controllers\Partnership;

use App\Controllers\Controller;

use App\Models\Partnership;

use App\Models\Person;

use DateTime;

use DateInterval;

use Respect\Validation\Validator as v;

class PartnershipController extends Controller {

/**************************************************************************
* A partnership is a relationship between two persons
* known as person_1 and person_2.
* personId refers to either person_1 or person_2;
* partnerId refers to either person_2 or person_1 (the reverse of personId)
***************************************************************************/


	/******************************************************************
	* CREATE
	* Add a partnership for this person.
	* The partnership is assumed to be a totally new partnership.
	* Note that two people can enter into a partnership more than once
	* (by re-marrying, after divorce, for example).
	* Create a new partnership and add a link for each person.
	* The link added to the partner field indicates the current
	* partner (blank if currently not in a partnership - no records
	* exist or divorced and not entered into another relationship).
	*******************************************************************/
	public function getAddPartnership($request, $response, $args) {
		$personId = $args['personId'];
		$memberId = $_SESSION['member'];

		// Enable user to choose from their list of favourite/remembered people
		$favourites = \App\Models\Favourite::
									where('member_id', '=', $memberId)
									->get();

		// Get favourite names
		$favouriteNames = [];
		foreach ($favourites as $favourite) {
			$person = \App\Models\Person::find($favourite['person_id']);
			$favouriteNames[] = ['name'=> $person->fullName(),
								 'personId'=> $person['id']
								 ];
		}

		return $this->container->view->render($response, 'Knowledgebase/Person/createPartnership.twig', compact('personId', 'favouriteNames'));
	}

	public function postAddPartnership($request, $response, $args) {
		$personId = $args['personId'];
		$memberId = $_SESSION['member'];

		$valueInput = $request->getParam('inputType');

		$today = new DateTime();
		$tomorrow = new DateTime('+1 days');

		// A person can be found either via name and date of birth ('form') or via favourites list ('')
		if ($valueInput === 'form') {
			// Find person by searching knowledgebase
			$validation = $this->container->validator->validate($request, [
				'firstName' => v::notEmpty()->alpha('-'),
				'nickname' => V::optional(v::alpha()),
				'middleName' => v::optional(v::alpha('-')),
				'lastName' => v::notEmpty()->alpha('-'),
				'gender' => v::optional(v::alpha()), // not required: is selected from pull down list
				'dateOfBirth' => v::notEmpty()->date('d/m/Y'), // probably not required as date selected from date picker
				'placeOfBirth' => v::optional(v::alpha('-')),
				'marriageDate' => v::optional(v::date('d/m/Y'))->beforeDate($request->getParam('divorceDate')),
				'divorceDate' => v::optional(v::date('d/m/Y'))->beforeDate($tomorrow),
			]);

			if ($validation->failed()) {
				$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
				return $response->withRedirect($this->container->router->pathFor('addPartnership', ['personId'=>$personId]));
			};

			// Determine who the partner is (to obtain an id).
			// Is there a record for the partner in the knowledgebase?
			// If so, need the user to verify the partner
			// If partner not in knowledgebase, it should be created.
			// What is the minimum amount of data required to create a person record?
			// 		first name, last name, year of birth
			// 	(note that a user may only know a knickname)
			
			$threshold = 100;
			$firstName =  standardizeName($request->getParam('firstName'));
			$nickname =  standardizeName($request->getParam('nickname'));
			$middleName =  standardizeName($request->getParam('middleName'));
			$lastName =  standardizeName($request->getParam('lastName'));
			$gender = lcfirst(substr($request->getParam('partnerGender'), 0, 1));
			$dateOfBirth = $this->reverseDate($request->getParam('dateOfBirth'));
			$placeOfBirth =  standardizeName($request->getParam('placeOfBirth'));

			$results = Person::findPeople($firstName, $lastName, $gender, $dateOfBirth, $placeOfBirth, $threshold);

			$people = $results['people'];

			if (count($people) === 0) {
				// No one with that name is in our knowledgebase - create the person
				$person = Person::create([
					'first_name' => $firstName,
					'nickname' => $nickname,
					'middlename' => $middleName,
					'last_name' => $lastName,
					'gender' => $gender,
					'date_of_birth' => $dateOfBirth,
					'place_of_birth' => $placeOfBirth
				]);

				$partnerId = $person->id;
			} elseif (count($people) === 1) {
				// Partner is in knowledgebase; get their id
				$partnerId = $people[0]['id'];
			} else {
				// More than one person found - which one is it, if any?
				// This is a stop-gap solution; user needs to be shown list and can choose.
				// The appropriate person may not be in list so need to create person.
				$this->container->flash->addMessage('error', "There is more than one person in knowledgebase with those details; more information required.");
				return $response->withRedirect($this->container->router->pathFor('addPartnership', ['personId'=>$personId]));
			}

		} else {
			// person taken from 'remembered' list
			$partnerNo = $request->getParam('partnerName');
			$p = split('~', $partnerNo);
			$partnerId = $p[0];
			
		};

		// Further validation
		// Cannot be your own partner
		if ($personId === $partnerId) {
			$this->container->flash->addMessage('info', "Partner same as person: not allowed.");
			return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
		}

		// Validate dates
		$marriageDate = $request->getParam('marriageDate');
		$divorceDate = $request->getParam('divorceDate');

		$marriageDate = date_format(date_create_from_format("d/m/Y", $marriageDate), "Y-m-d");
		if ($divorceDate) {
			$divorceDate = date_format(date_create_from_format("d/m/Y", $divorceDate), "Y-m-d");
		} else {
			$divorceDate = "0000-00-00";
		}
 
		// divorce date should be after marriage date
		if ($divorceDate != "0000-00-00" ) {
			if ($marriageDate >= $divorceDate) {
				$this->container->flash->addMessage('info', "The partnership end date should be after the start date. Please re-enter dates.");
				return $response->withRedirect($this->container->router->pathFor('addPartnership', ['personId'=>$personId, 'memberId'=>$memberId]));
			};
		};
		
		// is data to be kept private?
		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		$partnershipId = \App\Models\Partnership::insertGetId([
							'person_1' => $personId,
							'person_2' => $partnerId,
							'marriage_date' => $marriageDate,
							'divorce_date' => $divorceDate,
							'private' => $private
						]);


		// Update this person's record with current partner:
		// need not be the one in the partnership just added,
		// may be divorced, so no partner
		
		$this->updateCurrentPartner($personId);

        // Update partner's record

        $this->updateCurrentPartner($partnerId);

        // Make a notice that this partnership has been created if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Partnership', $personId);
		}


		$this->container->flash->addMessage('info', "A new partnership added.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}

	private function updateCurrentPartner($personId) {
		// Need all partnership records for this person
		$partnerships = \App\Models\Partnership::
									where ('person_1', '=', $personId)
									->orWhere('person_2', '=', $personId)
									->get();

		// Want partnership with latest marriage date
		// There is at least one item in this list - the one just added!
		$latestDate = "0000-00-00";

		foreach ($partnerships as $partnership) {
			if ($partnership['marriage_date'] > $latestDate) {
				$latestPartnership = $partnership;
			}
			
		};


		if ( ($latestPartnership['divorce_date'] === "0000-00-00")) {

			// Update this person's partner field
			$latestPartnerId = ($latestPartnership['person_1'] == $personId) ? $latestPartnership['person_2'] : $latestPartnership['person_1'];
			
			\App\Models\Person:: where('id', $personId)
	            	->update(['current_partner' => $latestPartnerId]);
	    } else {
	    	// Not in a relationship currently
	    	\App\Models\Person:: where('id', $personId)
	            	->update(['current_partner' => null]);
	    }

	}


/***************************************************************************
* UPDATE
* Update a partnership: 
* only allows update of dates of partnership
*****************************************************************************/

	public function getUpdatePartnership($request, $response, $args) {
		$personId = $args['personId'];
		$partnershipId = $args['partnershipId'];
		
		$partnership = \App\Models\Partnership::find($partnershipId);
		$partnerName = $partnership->partnerName($personId);


		$marriageDate = $partnership['marriage_date'];
		$divorceDate = $partnership['divorce_date'];

		// Convert date format from database to output format
		$marriageDate = date_format(date_create_from_format("Y-m-d", $marriageDate), "d/m/Y");
		$divorceDate = date_format(date_create_from_format("Y-m-d", $divorceDate), "d/m/Y");

		
		return $this->container->view->render($response, 'Knowledgebase/Person/updatePartnership.twig', compact('personId', 'partnership', 'partnerName', 'marriageDate', 'divorceDate'));
	}

	public function postUpdatePartnership($request, $response, $args) {
		// Many ramifications depending on nature of update
		// Only allow dates to be updated (a change of partner is achieved by a delete followed by new partnership)
		// If marriage date is altered this may affect current partnership
		// If divorce date changed, could affect current partnership 
		// If divorce date removed, will affect current partnership
		$personId = $args['personId'];
		$partnershipId = $args['partnershipId'];

		$marriageDate = $request->getParam('marriageDate');
		$divorceDate = $request->getParam('divorceDate');	

		$today = new DateTime();
		$tomorrow = $today->add(new DateInterval('P1D'));
		$validation = $this->container->validator->validate($request, [
			'marriageDate' => v::optional(v::date('d/m/Y'))->beforeDate('divorceDate'),
			'divorceDate' => v::optional(v::date('d/m/Y'))->beforeDate($tomorrow)
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('postUpdatePartnership', ['personId'=>$personId, 'partnershipId'=>$partnershipId]));
		};

		// Convert date format (from input to model)
		$marriageDate = date_format(date_create_from_format("d/m/Y", $request->getParam('marriageDate')), "Y-m-d");
		$divorceDate = date_format(date_create_from_format("d/m/Y", $request->getParam('divorceDate')), "Y-m-d");

		// Is private checkbox checked?
		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		// Update partnership
		\App\Models\Partnership::
							where('id', $partnershipId)
            				->update(['marriage_date' => $marriageDate, 
            						  'divorce_date' => $divorceDate,
            						  'private' => $private
            						  ]);

        // If marriageDate or divorcedate has changed then it may be the case that the current partner must be updated
        // Just find current partner by searching all partnerships
        // and then update person and their partner
        $partnership = \App\Models\Partnership::find($partnershipId);
        $partnerId = ($partnership['person_1'] === $personId) ? $partnership['person_2'] : $partnershipId['person_1'];

        $this->updateCurrentPartner($personId);
		$this->updateCurrentPartner($partnerId);

		// Update private flag 
		// Get the medical-link entry for this medical (there should only be one)
		$partnership = \App\Models\Partnership::
										where('partner_id', '=', $partnerId)
										->first();
		// Only update if flag has changed
		if ($partnership['private'] != $private) {
			\App\Models\Partnership::
						where('partner_id', '=', $partnerId)
						->update(['private' => !$private]);
		}

		// Issue update notice (if public i.e. not private)
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Partnership', $personId);
		}

		$this->container->flash->addMessage('info', "Partnership dates updated for this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId, 'partnershipId'=>$partnershipId]));

	}


/*************************************************************************************
* DELETE
* Deletes a partnership and resets current partner for each person in the partnership
* ************************************************************************************/

	public function deletePartnership($request, $response, $args) {
		$personId = $args['personId'];
		$partnershipId = $args['partnershipId'];

		$partnership = \App\Models\Partnership::find($partnershipId);
		$partnerId = ($partnership['person_1'] === $personId) ? $partnership['person_2'] : $partnershipId['person_1'];

		// Is partnership private?
		$private = \App\Models\Partnership::find($partnershipId)->value('private');

		// Delete partnership link
		\App\Models\Partnership::
						where('id', '=', $partnershipId)
						->delete();

		// May need to update the two partners' spouse/partner fields
		$this->updateCurrentPartner($personId);
		$this->updateCurrentPartner($partnerId);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Partnership', $personId);
		}

		$this->container->flash->addMessage('info', "Partnership (" . $partnershipId . ") deleted from this person's (" . $personId . ")record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

/*******************************************************************************
* READ
* Is data to be kept private or can it be read/updated/deleted by other users?
* ******************************************************************************/

	public function updatePrivate($request, $response, $args) {
		$partnershipId = $args['partnershipId'];
		$personId = $args['personId'];
		$p = \App\Models\Partnership::where('id', $partnershipId)->value('private');
		\App\Models\Partnership::
								where('id', '=', $partnershipId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Partnership (" . $partnershipId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					
	}

	

}