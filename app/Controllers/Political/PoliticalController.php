<?php

namespace App\Controllers\Political;

use App\Controllers\Controller;

use App\Models\Political;

use App\Models\Person;

use Respect\Validation\Validator as v;

class PoliticalController extends Controller {




	/********************************************************
	* CREATE
	* Add a politic for this person.
	* The politic is assumed to be a totally new politic.
	* Creare new politic and add a link for this person.
	**********************************************************/
	
	public function getAddPolitical($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createPolitical.twig', compact('personId'));
	}

	public function postAddPolitical($request, $response, $args) {
		$personId = $args['personId'];

		// The following items need to be validated
		$date = $request->getParam('date');
		$political = $request->getParam('political');

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));	

		// Create a new address
		$addressId = \App\Models\Political::insertGetId([
							'date' => $date,
							'political' => $political
						]);

		// Create a new address-link
		\App\Models\PoliticalLink:: insert([
							'person_id' => $personId,
							'politic_id' => $politicalId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Political', $personId);
		}


		$this->container->flash->addMessage('info', "A new political activity added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}


	/************************************************************************
	* UPDATE
	* Amend an existing politic.
	*************************************************************************/
	public function getUpdatePolitical($request, $response, $args) {
		$personId = $args['personId'];
		$politicalId = $args['politicalId'];
		
		$politic = \App\Models\Political::find($politicalId);
		$politicLink = \App\Models\PoliticalLink::where('political_id', '=', $politicalId)->first();
		$private = $politicLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updatePolitical.twig', compact('personId', 'political', 'private'));
	}

	public function postUpdatePolitical($request, $response, $args) {
		$personId = $args['personId'];
		$politicalId = $args['politicalId'];

		// Get all the address-link entries for this address 
		$addressLinkIds = \App\Models\PoliticalLink::
						where('political_id', '=', $politicalId)
						->get();

		// The following items need to be validated
		$date = $request->getParam('date');
		$political = $request->getParam('political');									

		\App\Models\Political::
							where('id', '=', $politicalId)
							->update([
								'date' => $date,
								'political' => $political,
							]);

		// Update private flag 
		// Get the politic-link entry for this politic (there should only be one)
		$politicLink = \App\Models\PoliticalLink::
										where('political_id', '=', $politicalId)
										->first();
		// Only update if flag has changed
		if ($politicalLink['private'] != $private) {
			\App\Models\PoliticalLink::
						where('political_id', '=', $politicalId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Political', $personId);
		}

		$this->container->flash->addMessage('info', "Political activity updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/****************************************************************
	* DELETE
	* Remove politic from Politics and person's link to this politic 
	*****************************************************************/

	public function deletePolitical($request, $response, $args) {
		$personId = $args['personId'];
		$politicalId = $args['politicalId'];

		// Delete address link
		\App\Models\PoliticLink::
						where('person_id', '=', $personId)
						->where('political_id', '=', $politicalId)
						->delete();

		// Delete address
		\App\Models\Political::
						where ('id', '=', $politicalId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Political', $personId);
		}

		$this->container->flash->addMessage('info', "Political activity deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	/***********************************************************************
	* Toggle private status of an address
	************************************************************************/
	public function updatePrivatePolitical($request, $response, $args) {
		$politicalLinkId = $args['politicalLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\PoliticalLink::where('id', $politicalLinkId)->value('private');
		\App\Models\PoliticalLink::
								where('id', '=', $politicalLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Political activity (" . $politicalLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}