<?php

namespace App\Controllers\Military;

use App\Controllers\Controller;

use App\Models\Military;

use App\Models\Person;

use Respect\Validation\Validator as v;

class MilitaryController extends Controller {

	/*********************************************************
	* VALIDATE
	**********************************************************/
	private function validate($request, $response, $personId, $returnTo) {
		$validation = $this->container->validator->validate($request, [
			'branch' => v::notEmpty()->alpha('-'),
			'group' => v::optional(v::alpha('-,()')),
			'rank' => v::optional(v::alpha('-,()')),
			'awards' => v::optional(v::alpha('-,()')),
			'start_date' => v::optional(v::date('d/m/Y')),
			'end_date' => v::optional(v::date('d/m/Y')),
			'description' => v::optional(v::alpha('-,()')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor($returnTo, ['personId'=>$personId]));
		};

	}

	/********************************************************
	* SET FIELDS 
	*********************************************************/
	private function setFields($request) {
		// Convert date format from input to database format
		$start_date = date_format(date_create_from_format("d/m/Y", $request->getParam('start_date')), "Y-m-d");
		$end_date = date_format(date_create_from_format("d/m/Y", $request->getParam('end_date')), "Y-m-d");

		$fields = [
				'branch' => $request->getParam('branch'),
				'group' => $request->getParam('group'),
				'rank' => $request->getParam('rank'),
				'awards' =>  $request->getParam('awards'),
				'start_date' => $start_date,
				'end_date' => $end_date,
				'description' => $request->getParam('description')
			];

			
		return $fields;
	}

	
	/*********************************************************
	* CREATE
	* Add a military event for this person.
	* The military event is assumed to be a totally new event.
	* Creare new event and add a link for this person.
	**********************************************************/
	public function getAddMilitary($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createMilitary.twig', compact('personId'));
	}

	public function postAddMilitary($request, $response, $args) {
		$personId = $args['personId'];

/*
		$validation = $this->container->validator->validate($request, [
			'branch' => v::notEmpty()->alpha('-'),
			'group' => v::optional(v::alpha('-,()')),
			'rank' => v::optional(v::alpha('-,()')),
			'awards' => v::optional(v::alpha('-,()')),
			'start_date' => v::optional(v::date('d/m/Y')),
			'end_date' => v::optional(v::date('d/m/Y'))
			'description' => v::optional(v::alpha('-,()')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addMilitary', ['personId'=>$personId]));
		};
*/
		$this->validate($request, $response, $personId, 'addMilitary');

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new military
		$militaryId = \App\Models\Military::insertGetId($this->setFields($request));

		// Create a new military-link
		\App\Models\MilitaryLink:: insert([
							'person_id' => $personId,
							'military_id' => $militaryId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Military', $personId);
		}


		$this->container->flash->addMessage('info', "A new military event added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}
	
	/************************************************************************
	* UPDATE
	* Amend an existing military.
	*************************************************************************/
	public function getUpdateMilitary($request, $response, $args) {
		$personId = $args['personId'];
		$militaryId = $args['militaryId'];
		
		$military = \App\Models\Military::find($militaryId);
		$militaryLink = \App\Models\MilitaryLink::where('military_id', '=', $militaryId)->first();
		$private = $militaryLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateMilitary.twig', compact('personId', 'military', 'private'));
	}


	public function postUpdateMilitary($request, $response, $args) {
		$personId = $args['personId'];
		$militaryId = $args['militaryId'];
/*
		$validation = $this->container->validator->validate($request, [
			'branch' => v::notEmpty()->alpha('-'),
			'group' => v::optional(v::alpha('-,()')),
			'rank' => v::optional(v::alpha('-,()')),
			'awards' => v::optional(v::alpha('-,()')),
			'start_date' => v::optional(v::date('d/m/Y')),
			'end_date' => v::optional(v::date('d/m/Y'))
			'description' => v::optional(v::alpha('-,()')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('updateMilitary', ['personId'=>$personId]));
		};
*/
		$this->validate($request, $personId, 'updateMilitary');

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));
								
		//Update military entry
		\App\Models\Military::
							where('id', '=', $militaryId)
							->update($this->setFields($request));

		// Update private flag 
		// Get the military-link entry for this military (there should only be one)
		$militaryLink = \App\Models\MilitaryLink::
										where('military_id', '=', $militaryId)
										->first();
		// Only update if flag has changed
		if ($militaryLink['private'] != $private) {
			\App\Models\MilitaryLink::
						where('military_id', '=', $militaryId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Military', $personId);
		}

		$this->container->flash->addMessage('info', "Military event updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove military from Militarys and person's ink to this military 
	*****************************************************************/

	public function deleteMilitary($request, $response, $args) {
		$personId = $args['personId'];
		$militaryId = $args['militaryId'];

		// Delete military link
		\App\Models\MilitaryLink::
						where('person_id', '=', $personId)
						->where('military_id', '=', $militaryId)
						->delete();

		// Delete military
		\App\Models\Military::
						where ('id', '=', $militaryId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Military', $personId);
		}

		$this->container->flash->addMessage('info', "Military event deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a military event
	************************************************************************/
	public function updatePrivateMilitary($request, $response, $args) {
		$militaryLinkId = $args['militaryLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\MilitaryLink::where('id', $militaryLinkId)->value('private');
		\App\Models\MilitaryLink::
								where('id', '=', $militaryLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Military event (" . $militaryLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

}