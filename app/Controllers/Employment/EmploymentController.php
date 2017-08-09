<?php

namespace App\Controllers\Employment;

use App\Controllers\Controller;

use App\Models\Employment;

use App\Models\Person;

use Respect\Validation\Validator as v;

class AddressController extends Controller {


	
	

	/********************************************************
	* CREATE
	* Add a employment for this person.
	* The employment is assumed to be a totally new employment.
	* Creare new employment and add a link for this person.
	**********************************************************/
	public function getAddEmployment($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createEmployment.twig', compact('personId'));
	}

	public function postAddEmployment($request, $response, $args) {
		$personId = $args['personId'];

		// The following items need to be validated
		$date = $request->getParam('date');
		$employment = $request->getParam('employment');

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));	

		// Create a new employment
		$addressId = \App\Models\Employment::insertGetId([
							'date' => $date,
							'employment' => $employment
						]);

		// Create a new employment-link
		\App\Models\EmploymentLink:: insert([
							'person_id' => $personId,
							'employment_id' => $employmentId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Employment', $personId);
		}

		$this->container->flash->addMessage('info', "A new employment added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}

	/************************************************************************
	* UPDATE
	* Amend an existing employment.
	*************************************************************************/
	public function getUpdateEmployment($request, $response, $args) {
		$personId = $args['personId'];
		$employmentId = $args['employmentId'];
		
		$employment = \App\Models\Employment::find($employmentId);
		$employmentLink = \App\Models\EmploymentLink::where('employment_id', '=', $employmentId)->first();
		$private = $employmentLink['private'];

		//dump($pastime, $startYear, $endYear); die();
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateEmployment.twig', compact('personId', 'employment', 'private'));
	}

	public function postUpdateEmployment($request, $response, $args) {
		$personId = $args['personId'];
		$employmentId = $args['employmentId'];

		// Get all the address-link entries for this address 
		$employmentLinkIds = \App\Models\EmploymentLink::
						where('employment_id', '=', $employmentId)
						->get();

		$validation = $this->container->validator->validate($request, [
			'start_year' => v::optional(v::intType()->between(0,9999)),
			'end_year' => v::optional(v::intType()->between('start_year',9999)),
			'job_title' => v::optional(v::alpha()),
			'employer' => v::optional(v::alpha('-')),
			'location' => v::optional(v::alpha('-'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addEmployment', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		// The following items need to be validated
		$date = $request->getParam('date');
		$employment = $request->getParam('employment');									

		\App\Models\Employment::
							where('id', '=', $employmentId)
							->update([
								'date' => $date,
								'employment' => $employment,
							]);

		// Update private flag 
		// Get the employment-link entry for this employment (there should only be one)
		$employmentLink = \App\Models\EmploymentLink::
										where('employment_id', '=', $employmentId)
										->first();
		// Only update if flag has changed
		if ($employmentLink['private'] != $private) {
			\App\Models\EmploymentLink::
						where('employment_id', '=', $employmentId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Employment', $personId);
		}

		$this->container->flash->addMessage('info', "Employment updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	/*************************************************************************
	* DELETE
	* Remove employment from Employments and person's link to this employment 
	*************************************************************************/

	public function deleteEmployment($request, $response, $args) {
		$personId = $args['personId'];
		$employmentId = $args['employmentId'];

		// Delete address link
		\App\Models\EmploymentLink::
						where('person_id', '=', $personId)
						->where('employment_id', '=', $employmentId)
						->delete();

		// Delete address
		\App\Models\Employment::
						where ('id', '=', $employmentId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Education', $personId);
		}

		$this->container->flash->addMessage('info', "Employment deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of an address
	************************************************************************/
	public function updatePrivateEmployment($request, $response, $args) {
		$employmentLinkId = $args['employmentLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\EmploymentLink::where('id', $employmentLinkId)->value('private');
		\App\Models\EmploymentLink::
								where('id', '=', $employmentLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Employment (" . $employmentLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}