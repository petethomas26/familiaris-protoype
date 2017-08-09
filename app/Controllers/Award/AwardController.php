<?php

namespace App\Controllers\Award;

use App\Controllers\Controller;

use App\Models\Award;

use App\Models\Person;

use Respect\Validation\Validator as v;

class AwardController extends Controller {

	/*********************************************************
	* CREATE
	* Add a award event for this person.
	* The award event is assumed to be a totally new event.
	* Creare new event and add a link for this person.
	**********************************************************/
	public function getAddAward($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createAward.twig', compact('personId'));
	}

	public function postAddAward($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'award' => v::notEmpty()->alpha('-'),
			'date' => v::optional(v::alpha()->between(0,9999)),
			'description' => v::notEmpty()->alpha('-')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addAward', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new award
		$awardId = \App\Models\Award::insertGetId([
							'award' => $request->getParam('award'),
							'date' => $request->getParam('date'),
							'description' => $request->getParam('description'),
						]);

		// Create a new award-link
		\App\Models\AwardLink:: insert([
							'person_id' => $personId,
							'award_id' => $awardId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Award', $personId);
		}


		$this->container->flash->addMessage('info', "A new award event added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}
	
	/************************************************************************
	* UPDATE
	* Amend an existing award.
	*************************************************************************/
	public function getUpdateAward($request, $response, $args) {
		$personId = $args['personId'];
		$awardId = $args['awardId'];
		
		$award = \App\Models\Award::find($awardId);
		$awardLink = \App\Models\AwardLink::where('award_id', '=', $awardId)->first();
		$private = $awardLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateAward.twig', compact('personId', 'award', 'private'));
	}


	public function postUpdateAward($request, $response, $args) {
		$personId = $args['personId'];
		$awardId = $args['awardId'];

		$validation = $this->container->validator->validate($request, [
			'award' => v::notEmpty()->alpha('-'),
			'date' => v::optional(v::intVal()->between(0,9999)),
			'description' => v::notEmpty()->alNum('-()')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addAward', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));
								
		//Update award entry
		\App\Models\Award::
							where('id', '=', $awardId)
							->update([
								'award' => $request->getParam('award'),
								'date' => $request->getParam('date'),
								'description' => $request->getParam('description'),
							]);

		// Update private flag 
		// Get the award-link entry for this award (there should only be one)
		$awardLink = \App\Models\AwardLink::
										where('award_id', '=', $awardId)
										->first();
		// Only update if flag has changed
		if ($awardLink['private'] != $private) {
			\App\Models\AwardLink::
						where('award_id', '=', $awardId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Award', $personId);
		}

		$this->container->flash->addMessage('info', "Award event updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove award from Awards and person's ink to this award 
	*****************************************************************/

	public function deleteAward($request, $response, $args) {
		$personId = $args['personId'];
		$awardId = $args['addressId'];

		// Delete address link
		\App\Models\AwardLink::
						where('person_id', '=', $personId)
						->where('award_id', '=', $awardId)
						->delete();

		// Delete address
		\App\Models\Award::
						where ('id', '=', $awardId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Award', $personId);
		}

		$this->container->flash->addMessage('info', "Award event deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a award event
	************************************************************************/
	public function updatePrivateAward($request, $response, $args) {
		$awardLinkId = $args['awardLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\AwardLink::where('id', $awardLinkId)->value('private');
		\App\Models\AwardLink::
								where('id', '=', $awardLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Award event (" . $awardLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

}