<?php

namespace App\Controllers\Medical;

use App\Controllers\Controller;

use App\Models\Medical;

use App\Models\Person;

use Respect\Validation\Validator as v;

class MedicalController extends Controller {

	/*********************************************************
	* CREATE
	* Add a medical event for this person.
	* The medical event is assumed to be a totally new event.
	* Creare new event and add a link for this person.
	**********************************************************/
	public function getAddMedical($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createMedical.twig', compact('personId'));
	}

	public function postAddMedical($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'condition' => v::notEmpty()->alpha('-'),
			'year' => v::optional(v::alpha()->between(0,9999)),
			'treatment' => v::notEmpty()->alpha('-')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addMedical', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new medical
		$medicalId = \App\Models\Medical::insertGetId([
							'condition' => $request->getParam('condition'),
							'year' => $request->getParam('year'),
							'treatment' => $request->getParam('treatment'),
						]);

		// Create a new medical-link
		\App\Models\MedicalLink:: insert([
							'person_id' => $personId,
							'medical_id' => $medicalId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Medical', $personId);
		}


		$this->container->flash->addMessage('info', "A new medical event added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=> $personId]));

	}
	
	/************************************************************************
	* UPDATE
	* Amend an existing medical.
	*************************************************************************/
	public function getUpdateMedical($request, $response, $args) {
		$personId = $args['personId'];
		$medicalId = $args['medicalId'];
		
		$medical = \App\Models\Medical::find($medicalId);
		$medicalLink = \App\Models\MedicalLink::where('medical_id', '=', $medicalId)->first();
		$private = $medicalLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateMedical.twig', compact('personId', 'medical', 'private'));
	}


	public function postUpdateMedical($request, $response, $args) {
		$personId = $args['personId'];
		$medicalId = $args['medicalId'];

		$validation = $this->container->validator->validate($request, [
			'condition' => v::notEmpty()->alpha('-'),
			'year' => v::optional(v::intVal()->between(0,9999)),
			'treatment' => v::notEmpty()->alNum('-()')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addMedical', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));
								
		//Update medical entry
		\App\Models\Medical::
							where('id', '=', $medicalId)
							->update([
								'condition' => $request->getParam('condition'),
								'year' => $request->getParam('year'),
								'treatment' => $request->getParam('treatment'),
							]);

		// Update private flag 
		// Get the medical-link entry for this medical (there should only be one)
		$medicalLink = \App\Models\MedicalLink::
										where('medical_id', '=', $medicalId)
										->first();
		// Only update if flag has changed
		if ($medicalLink['private'] != $private) {
			\App\Models\MedicalLink::
						where('medical_id', '=', $medicalId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Medical', $personId);
		}

		$this->container->flash->addMessage('info', "Medical event updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove medical from Medicals and person's ink to this medical 
	*****************************************************************/

	public function deleteMedical($request, $response, $args) {
		$personId = $args['personId'];
		$medicalId = $args['medicalId'];

		// Delete medical link
		\App\Models\MedicalLink::
						where('person_id', '=', $personId)
						->where('medical_id', '=', $medicalId)
						->delete();

		// Delete medical
		\App\Models\Medical::
						where ('id', '=', $medicalId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Medical', $personId);
		}

		$this->container->flash->addMessage('info', "Medical event deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a medical event
	************************************************************************/
	public function updatePrivateMedical($request, $response, $args) {
		$medicalLinkId = $args['medicalLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\MedicalLink::where('id', $medicalLinkId)->value('private');
		\App\Models\MedicalLink::
								where('id', '=', $medicalLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Medical event (" . $medicalLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

}