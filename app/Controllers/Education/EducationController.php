<?php


namespace App\Controllers\Education;

use App\Controllers\Controller;

use App\Models\Education;

use App\Models\EducationLink;

use App\Models\Person;

use Respect\Validation\Validator as v;

class EducationController extends Controller {

	/*********************************************************************
	* CREATE
	* Create a new education for this person
	**********************************************************************/

	public function getAddEducation($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createEducation.twig', compact('personId'));
	}


	/********************************************************
	* Add an education for this person.
	* Create new education and add a link for this person.
	**********************************************************/
	public function postAddEducation($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'institution' => v::notEmpty()->alpha('-,'),
			'startYear' => v::optional(v::intVal()->between(0,9999)),
			'endYear' => v::optional(v::intVal()->between(0,9999)),
			'qualification' => v::notEmpty()->alnum('-(),'),
			'subject' => v::optional(v::alpha('-,&'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addEducation', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));	

		// Create a new education
		$educationId = \App\Models\Education::insertGetId([
							'start_year' => $request->getParam('startYear'),
							'end_year' => $request->getParam('endYear'),
							'institution' => $request->getParam('institution'),
							'qualification' => $request->getParam('qualification'),
							'subject' => $request->getParam('subject')
						]);

		// Create a new education-link
		\App\Models\EducationLink:: insert([
							'person_id' => $personId,
							'education_id' => $educationId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Education', $personId);
		}

		$this->container->flash->addMessage('info', "A new education added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}
   
	/************************************************************************
	* UPDATE
	* Amend an existing education.
	*************************************************************************/
	public function getUpdateEducation($request, $response, $args) {
		$personId = $args['personId'];
		$educationId = $args['educationId'];
		
		$education = \App\Models\Education::find($educationId);
		
		$educationLink = \App\Models\EducationLink::where('education_id', '=', $educationId)->first();
		$private = $educationLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateEducation.twig', compact('personId', 'education', 'private'));
	}

	/************************************************************************
	* Amend an existing education.
	*************************************************************************/
	public function postUpdateEducation($request, $response, $args) {
		$personId = $args['personId'];
		$educationId = $args['educationId'];


		// Get all the education-link entries for this education 
		$educationLinkIds = \App\Models\EducationLink::
						where('education_id', '=', $educationId)
						->get();
			

		$validation = $this->container->validator->validate($request, [
			'institution' => v::notEmpty()->alpha('-,'),
			'startYear' => v::optional(v::intVal()->between(0,9999)),
			'endYear' => v::optional(v::intVal()->between(0,9999)),
			'qualification' => v::notEmpty()->alnum('-(),'),
			'subject' => v::optional(v::alpha('-,&'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('updateEducation', ['personId'=>$personId, 'educationId'=>$educationId]));
		};								


		\App\Models\Education::
							where('id', '=', $educationId)
							->update([
								'start_year' => $request->getParam('startYear'),
								'end_year' => $request->getParam('endYear'),
								'institution' => $request->getParam('institution'),
								'qualification' => $request->getParam('qualification'),
								'subject' => $request->getParam('subject')
							]);

		// Update private flag 
		// Get the education-link entry for this education (there should only be one)
		$educationLink = \App\Models\EducationLink::
										where('education_id', '=', $educationId)
										->first();
		// Only update if flag has changed
		if ($educationLink['private'] != $private) {
			\App\Models\EducationLink::
						where('education_id', '=', $educationId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Education', $personId);
		}

		$this->container->flash->addMessage('info', "Education updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/*******************************************************************
	* DELETE
	* Remove education from Education and person's ink to this education 
	********************************************************************/

	public function getDeleteEducation($request, $response, $args) {
		$personId = $args['personId'];
		$educationId = $args['educationId'];

		// Delete education link
		\App\Models\EducationLink::
						where('person_id', '=', $personId)
						->where('education_id', '=', $educationId)
						->delete();

		// Delete education
		\App\Models\Education::
						where ('id', '=', $educationId)
						->delete();

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Education', $personId);
		}

		$this->container->flash->addMessage('info', "Education deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of an education
	************************************************************************/
	public function updatePrivateEducation($request, $response, $args) {
		$educationLinkId = $args['educationLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\EducationLink::where('id', $educationLinkId)->value('private');
		\App\Models\EducationLink::
								where('id', '=', $educationLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Education (" . $educationLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}