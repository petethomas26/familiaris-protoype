<?php

namespace App\Controllers\Output;

use App\Controllers\Controller;

use App\Models\Output;

use App\Models\Person;

use Respect\Validation\Validator as v;

class OutputController extends Controller {

	/*********************************************************
	* CREATE
	* Add a output event for this person.
	* The output event is assumed to be a totally new event.
	* Creare new event and add a link for this person.
	**********************************************************/
	public function getAddOutput($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createOutput.twig', compact('personId'));
	}

	public function postAddOutput($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'output' => v::notEmpty()->alpha('-'),
			'year' => v::optional(v::intVal()->between(0,9999)),
			'collaborator' => v::optional(v::alpha('.-')),
			'description' => v::notEmpty()->alpha('-.,()')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addOutput', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new output
		$outputId = \App\Models\Output::insertGetId([
							'output' => $request->getParam('output'),
							'year' => $request->getParam('year'),
							'collaborator' => $request->getParam('collaborator'),
							'description' => $request->getParam('description')
						]);

		// Create a new output-link
		\App\Models\OutputLink:: insert([
							'person_id' => $personId,
							'output_id' => $outputId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Output', $personId);
		}


		$this->container->flash->addMessage('info', "A new output event added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}
	
	/************************************************************************
	* UPDATE
	* Amend an existing address.
	*************************************************************************/
	public function getUpdateOutput($request, $response, $args) {
		$personId = $args['personId'];
		$outputId = $args['outputId'];
		
		$output = \App\Models\Output::find($outputId);
		$outputLink = \App\Models\OutputLink::where('output_id', '=', $outputId)->first();
		$private = $outputLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateOutput.twig', compact('personId', 'output', 'private'));
	}


	public function postUpdateOutput($request, $response, $args) {
		$personId = $args['personId'];
		$outputId = $args['outputId'];

		$validation = $this->container->validator->validate($request, [
			'output' => v::notEmpty()->alpha('-'),
			'year' => v::optional(v::intVal()->between(0,9999)),
			'collaborator' => v::optional(v::alpha('.-')),
			'description' => v::notEmpty()->alpha('-.,()')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addOutput', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));
								
		//Update output entry
		\App\Models\Output::
							where('id', '=', $outputId)
							->update([
								'output' => $request->getParam('output'),
								'year' => $request->getParam('year'),
								'collaborator' => $request->getParam('collaborator'),
								'description' => $request->getParam('description')
							]);

		// Update private flag 
		// Get the output-link entry for this output (there should only be one)
		$outputLink = \App\Models\OutputLink::
										where('output_id', '=', $outputId)
										->first();
		// Only update if flag has changed
		if ($outputLink['private'] != $private) {
			\App\Models\OutputLink::
						where('output_id', '=', $outputId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Output', $personId);
		}

		$this->container->flash->addMessage('info', "Output event updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove output from Outputs and person's ink to this output 
	*****************************************************************/

	public function deleteOutput($request, $response, $args) {
		$personId = $args['personId'];
		$outputId = $args['addressId'];

		// Delete address link
		\App\Models\OutputLink::
						where('person_id', '=', $personId)
						->where('output_id', '=', $outputId)
						->delete();

		// Delete address
		\App\Models\Output::
						where ('id', '=', $outputId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Output', $personId);
		}

		$this->container->flash->addMessage('info', "Output event deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a output event
	************************************************************************/
	public function updatePrivateOutput($request, $response, $args) {
		$outputLinkId = $args['outputLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\OutputLink::where('id', $outputLinkId)->value('private');
		\App\Models\OutputLink::
								where('id', '=', $outputLinkId)
								->update(['private' =>  !$p]);
								
		
		$this->container->flash->addMessage('info', "Output event (" . $outputLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

}