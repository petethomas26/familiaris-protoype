<?php

namespace App\Controllers\Service;

use App\Controllers\Controller;

use App\Models\Service;

use App\Models\Person;

use Respect\Validation\Validator as v;

class ServiceController extends Controller {

	/*********************************************************
	* CREATE
	* Add a service for this person.
	* The service is assumed to be a totally new service.
	* Creare new service and add a link for this person.
	**********************************************************/
	public function getAddService($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createService.twig', compact('personId'));
	}

	public function postAddService($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'start_date' => v::optional(v::date('d/m/Y')), // probably not required as date selected from date picker
			'end_date' => v::optional(v::date('d/m/Y')), // probably not required as date selected from date picker
			'service' => v::notEmpty()->alnum('-(),'),
			'description' => v::optional(v::alpha('-,&'))
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addService', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));	

		// Create a new service
		$serviceId = \App\Models\Service::insertGetId([
							'start_date' => $request->getParam('start_date'),
							'end_date' => $request->getParam('end_date'),
							'service' => $request->getParam('service'),
							'description' => $request->getParam('description')
						]);

		// Create a new service-link
		\App\Models\ServiceLink:: insert([
							'person_id' => $personId,
							'service_id' => $serviceId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Service', $personId);
		}


		$this->container->flash->addMessage('info', "A new service added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}

	/************************************************************************
	* UPDATE
	* Amend an existing service.
	*************************************************************************/
	public function getUpdateService($request, $response, $args) {
		$personId = $args['personId'];
		$serviceId = $args['serviceId'];
		
		$service = \App\Models\Service::find($serviceId);
		$serviceLink = \App\Models\ServiceLink::where('service_id', '=', $serviceId)->first();
		$private = $serviceLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateService.twig', compact('personId', 'service', 'private'));
	}

	public function postUpdateService($request, $response, $args) {
		$personId = $args['personId'];
		$serviceId = $args['serviceId'];

		$validation = $this->container->validator->validate($request, [
			'start_date' => $request->getParam('start_date'),
			'end_date' => $request->getParam('end_date'),
			'service' => $request->getParam('service'),
			'description' => $request->getParam('description')
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('updateService', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		\App\Models\Service::
							where('id', '=', $serviceId)
							->update([
								'start_date' => $request->getParam('start_date'),
								'end_date' => $request->getParam('end_date'),
								'service' => $request->getParam('service'),
								'description' => $request->getParam('description')
							]);

		
		// Update private flag 
		// Get the service-link entry for this service (there should only be one)
		$serviceLink = \App\Models\ServiceLink::
										where('service_id', '=', $serviceId)
										->first();
		// Only update if flag has changed
		if ($serviceLink['private'] != $private) {
			\App\Models\ServiceLink::
						where('service_id', '=', $serviceId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Service', $personId);
		}

		$this->container->flash->addMessage('info', "Service updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove address from Addresses and person's ink to this address 
	*****************************************************************/

	public function deleteService($request, $response, $args) {
		$personId = $args['personId'];
		$serviceId = $args['serviceId'];

		// Delete address link
		\App\Models\ServiceLink::
						where('person_id', '=', $personId)
						->where('service_id', '=', $serviceId)
						->delete();

		// Delete address
		\App\Models\Service::
						where ('id', '=', $serviceId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Service', $personId);
		}

		$this->container->flash->addMessage('info', "Service deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a service
	************************************************************************/
	public function updatePrivateService($request, $response, $args) {
		$serviceLinkId = $args['serviceLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\ServiceLink::where('id', $serviceLinkId)->value('private');
		\App\Models\NoteLink::
								where('id', '=', $serviceLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Service (" . $serviceLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}