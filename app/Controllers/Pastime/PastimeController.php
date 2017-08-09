<?php

namespace App\Controllers\Pastime;

use App\Controllers\Controller;

use App\Models\Pastime;

use App\Models\Person;

use Respect\Validation\Validator as v;

class PastimeController extends Controller {

	/********************************************************
	* CREATE
	* Add a pastime for this person.
	* The pastime is assumed to be a totally new pastime.
	* Create new pastime and add a link for this person.
	**********************************************************/
	public function getAddPastime($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createPastime.twig', compact('personId'));
	}


	public function postAddPastime($request, $response, $args) {
		$personId = $args['personId'];

		// The following items need to be validated
		$validation = $this->container->validator->validate($request, [
			'start_year' => v::optional(alpha()),
			'end_year' => v::optional(alpha()),
			'activity' => v::notEmpty()->alpha('-'),
			'club' => v::optional(v::alpha()), 
			'description' => optional(v::alpha('()-'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addPastime', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new pastime
		$addressId = \App\Models\Pastime::insertGetId([
							'start_year' => $startYear,
							'end_year' => $endYear,
							'activity' => $activity,
							'club' => $club,
							'description' => $description
						]);

		// Create a new pastime-link
		\App\Models\PastimeLink:: insert([
							'person_id' => $personId,
							'pastime_id' => $pastimeId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Pastime', $personId);
		}

		$this->container->flash->addMessage('info', "A new pastime added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}


	
	/************************************************************************
	* UPDATE
	* Amend an existing pastime.
	*************************************************************************/
	public function getUpdatePastime($request, $response, $args) {
		$personId = $args['personId'];
		$pastimeId = $args['pastimeId'];
		
		$pastime = \App\Models\Pastime::find($pastimeId);
		$pastimeLink = \App\Models\PastimeLink::where('pastime_id', '=', $pastimeId)->first();
		$private = $pastimeLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updatePastime.twig', compact('personId', 'pastime', 'private'));
	}

	public function postUpdatePastime($request, $response, $args) {
		$personId = $args['personId'];
		$pastimeId = $args['pastimeId'];

		// Get all the pastime-link entries for this address 
		$addressLinkIds = \App\Models\PastimeLink::
						where('pastime_id', '=', $pastimeId)
						->get();	

		$validation = $this->container->validator->validate($request, [
			'start_year' => v::optional(v::alpha()),
			'end_year' => v::optional(v::alpha()),
			'activity' => v::optional(v::alpha()),
			'club' => v::optional(v::alpha('-')),
			'description' => v::optional(v::alpha('()-'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addPastime', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		\App\Models\Pastime::
							where('id', '=', $pastimeId)
							->update([
								'start_year' => $request->getParam('pastimeStartYear'),
								'end_year' => $request->getParam('pastimeEndYear'),
								'activity' => $request->getParam('pastimeActivity'),
								'club' => $request->getParam('pastimeClub'),
								'description' => $request->getParam('pastimeDescription'),
							]);

		// Update private flag 
		// Get the medical-link entry for this medical (there should only be one)
		$pastimeLink = \App\Models\PastimeLink::
										where('pastime_id', '=', $pastimeId)
										->first();
		// Only update if flag has changed
		if ($pastimeLink['private'] != $private) {
			\App\Models\PastimeLink::
						where('pastime_id', '=', $pastimeId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Pastime', $personId);
		}

		$this->container->flash->addMessage('info', "Pastime updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/***********************************************************************
	* READ
	* Toggle private status of a pastime
	************************************************************************/
	public function updatePrivatePastime($request, $response, $args) {
		$pastimeLinkId = $args['pastimeLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\PastimeLink::where('id', $pastimeLinkId)->value('private');
		\App\Models\PastimeLink::
								where('id', '=', $pastimeLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Pastime (" . $pastimeLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

	/****************************************************************
	* DELETE
	* Remove pastime from pastimes and person's link to this pastime 
	*****************************************************************/

	public function deletePastime($request, $response, $args) {
		$personId = $args['personId'];
		$pastimeId = $args['pastimeId'];

		// Delete pastime link
		\App\Models\PastimeLink::
						where('person_id', '=', $personId)
						->where('pastime_id', '=', $pastimeId)
						->delete();

		// Delete pastime
		\App\Models\Pastime::
						where ('id', '=', $pastimeId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Pastime', $personId);
		}

		$this->container->flash->addMessage('info', "Pastime deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}



}