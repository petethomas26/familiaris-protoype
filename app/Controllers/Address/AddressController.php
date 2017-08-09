<?php

namespace App\Controllers\Address;

use App\Controllers\Controller;

use App\Models\Address;

use App\Models\Person;

use Respect\Validation\Validator as v;

class AddressController extends Controller {

	/*********************************************************
	* CREATE
	* Add an address for this person.
	* The address is assumed to be a totally new address.
	* Creare new address and add a link for this person.
	**********************************************************/
	public function getAddAddress($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createAddress.twig', compact('personId'));
	}


	public function postAddAddress($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'houseNumber' => v::notEmpty()->alNum('-'),
			'street' => V::notEmpty()->alpha('-'),
			'address2' => v::optional(v::alpha('-')),
			'town' => v::notEmpty()->alpha('-'),
			'postcode' => v::optional(v::alNum()), 
			'start_date' => v::optional(v::date('d/m/Y')), // probably not required as date selected from date picker
			'end_date' => v::optional(v::date('d/m/Y')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addAddress', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new address
		$addressId = \App\Models\Address::insertGetId([
							'houseNo_Name' =>  $request->getParam('houseNumber')),
							'address_1' =>  $this->standardizeName($request->getParam('street')),
							'address_2' =>  $this->standardizeName($request->getParam('address2')),
							'town' =>  $this->standardizeName($request->getParam('town')),
							'postcode' => $request->getParam('postcode'),
							'from_date' => $request->getParam('start_date'),
							'to_date' => $request->getParam('end_date')
						]);

		// Create a new address-link
		\App\Models\AddressLink:: insert([
							'person_id' => $personId,
							'address_id' => $addressId,
							'private' => $private
						]);

		// Make a notice that this address has been created if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Address', $personId);
		}


		$this->container->flash->addMessage('info', "A new address added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}

	

	/************************************************************************
	* UPDATE
	* Amend an existing address.
	*************************************************************************/
	public function getUpdateAddress($request, $response, $args) {
		$personId = $args['personId'];
		$addressId = $args['addressId'];
		
		$address = \App\Models\Address::find($addressId);
		$addressLink = \App\Models\AddressLink::where('address_id', '=', $addressId)->first();
		$private = $addressLink['private'];

		$fromDate = $address['from_date'];
		$toDate = $address['to_date'];

		// Convert date format from database to output format
		$fromDate = date_format(date_create_from_format("Y-m-d", $fromDate), "d/m/Y");
		$toDate = date_format(date_create_from_format("Y-m-d", $toDate), "d/m/Y");
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateAddress.twig', compact('personId', 'address', 'fromDate', 'toDate', 'private'));
	}


	public function postUpdateAddress($request, $response, $args) {
		$personId = $args['personId'];
		$addressId = $args['addressId'];

		$validation = $this->container->validator->validate($request, [
			'houseNumber' => v::notEmpty()->alNum('-'),
			'street' => V::notEmpty()->alpha('-'),
			'address_2' => v::optional(v::alpha('-')),
			'town' => v::notEmpty()->alpha('-'),
			'postcode' => v::optional(v::alNum()), // not required: is selected from pull down list
			'start_date' => v::optional(v::date('d/m/Y')), // probably not required as date selected from date picker
			'end_date' => v::optional(v::date('d/m/Y')),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addAddress', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));

		// Get all the address-link entries for this address 
		$addressLinkIds = \App\Models\AddressLink::
						where('address_id', '=', $addressId)
						->get();								

		\App\Models\Address::
							where('id', '=', $addressId)
							->update([
								'houseNo_Name' =>  $request->getParam('houseNumber'),
								'address_1' =>  $this->standardizeName($request->getParam('street')),
								'address_2' =>  $this->standardizeName($request->getParam('address2'),
								'town' =>  $this->standardizeName($request->getParam('town')),
								'postcode' => $request->getParam('postcode'),
								'from_date' => $request->getParam('start_date'),
								'to_date' => $request->getParam('end_date')
							]);

		// Update private flag 
		// Get the address-link entry for this address (there should only be one)
		$medicalLink = \App\Models\AddressLink::
										where('address_id', '=', $addressId)
										->first();
		// Only update if flag has changed
		if ($addressLink['private'] != $private) {
			\App\Models\AddressLink::
						where('address_id', '=', $addressId)
						->update(['private' => !$private]);
		}

		// Make a notice that this address has been created if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Address', $personId);
		}

		$this->container->flash->addMessage('info', "Address updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	/****************************************************************
	* DEETE
	* Remove address from Addresses and person's link to this address 
	*****************************************************************/

	public function getDeleteAddress($request, $response, $args) {
		$personId = $args['personId'];
		$addressId = $args['addressId'];

		// Delete address link
		\App\Models\AddressLink::
						where('person_id', '=', $personId)
						->where('address_id', '=', $addressId)
						->delete();

		// Delete address
		\App\Models\Address::
						where ('id', '=', $addressId)
						->delete();
		
		// Make a notice that this address has been deleted if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Address', $personId);
		}

		$this->container->flash->addMessage('info', "Address deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/***********************************************************************
	* Toggle private status of an address
	************************************************************************/
	public function updatePrivateAddress($request, $response, $args) {
		$addressLinkId = $args['addressLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\AddressLink::where('id', $addressLinkId)->value('private');
		\App\Models\AddressLink::
								where('id', '=', $addressLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Address (" . $addressLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}