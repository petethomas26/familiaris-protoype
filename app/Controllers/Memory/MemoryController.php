<?php

namespace App\Controllers\Memory;

use App\Controllers\Controller;

use App\Models\Memory;

use App\Models\Person;

use Respect\Validation\Validator as v;

class MemoryController extends Controller {

	/*********************************************************
	* CREATE
	* Add a memory event for this person.
	* The memory event is assumed to be a totally new event.
	* Creare new event and add a link for this person.
	**********************************************************/
	public function getAddMemory($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createMemory.twig', compact('personId'));
	}

	public function postAddMemory($request, $response, $args) {
		$personId = $args['personId'];

		$validation = $this->container->validator->validate($request, [
			'memory' => v::notEmpty()->alpha('-().'),
			'year' => v::optional(v::alpha()->between(0,9999)),
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addMemory', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));


		// Create a new memory
		$memoryId = \App\Models\Memory::insertGetId([
							'memory' => $request->getParam('memory'),
							'year' => $request->getParam('year'),
						]);

		// Create a new memory-link
		\App\Models\MemoryLink:: insert([
							'person_id' => $personId,
							'memory_id' => $memoryId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Memory', $personId);
		}


		$this->container->flash->addMessage('info', "A new memory event added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}
	
	/************************************************************************
	* UPDATE
	* Amend an existing memory.
	*************************************************************************/
	public function getUpdateMemory($request, $response, $args) {
		$personId = $args['personId'];
		$memoryId = $args['memoryId'];
		
		$memory = \App\Models\Memory::find($memoryId);
		$memoryLink = \App\Models\MemoryLink::where('memory_id', '=', $memoryId)->first();
		$private = $memoryLink['private'];
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateMemory.twig', compact('personId', 'memory', 'private'));
	}


	public function postUpdateMemory($request, $response, $args) {
		$personId = $args['personId'];
		$memoryId = $args['memoryId'];

		$validation = $this->container->validator->validate($request, [
			'memory' => v::notEmpty()->alpha('-'),
			'year' => v::optional(v::intVal()->between(0,9999)),
			
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "The marked fields are invalid. Please re-enter your detail(s)");
			return $response->withRedirect($this->container->router->pathFor('addMemory', ['personId'=>$personId]));
		};

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));
								
		//Update memory entry
		\App\Models\Memory::
							where('id', '=', $memoryId)
							->update([
								'memory' => $request->getParam('condition'),
								'year' => $request->getParam('year'),
								
							]);

		// Update private flag 
		// Get the memory-link entry for this memory (there should only be one)
		$memoryLink = \App\Models\MemoryLink::
										where('memory_id', '=', $memoryId)
										->first();
		// Only update if flag has changed
		if ($memoryLink['private'] != $private) {
			\App\Models\MemoryLink::
						where('memory_id', '=', $memoryId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Memory', $personId);
		}

		$this->container->flash->addMessage('info', "Memory event updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	

	/****************************************************************
	* DELETE
	* Remove memory from Memories and person's ink to this memory 
	*****************************************************************/

	public function deleteMemory($request, $response, $args) {
		$personId = $args['personId'];
		$memoryId = $args['memoryId'];

		// Delete memory link
		\App\Models\MemoryLink::
						where('person_id', '=', $personId)
						->where('memory_id', '=', $memoryId)
						->delete();

		// Delete memory
		\App\Models\Memory::
						where ('id', '=', $memoryId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Memory', $personId);
		}

		$this->container->flash->addMessage('info', "Memory event deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}


	/***********************************************************************
	* Toggle private status of a memory event
	************************************************************************/
	public function updatePrivateMemory($request, $response, $args) {
		$memoryLinkId = $args['memoryLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\MemoryLink::where('id', $memoryLinkId)->value('private');
		\App\Models\MemoryLink::
								where('id', '=', $memoryLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Memory event (" . $memoryLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}

}