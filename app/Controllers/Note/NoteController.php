<?php

namespace App\Controllers\Note;

use App\Controllers\Controller;

use App\Models\Note;

use App\Models\Person;

use Respect\Validation\Validator as v;

class AddressController extends Controller {

	/*********************************************************
	* CREATE
	* Add a note for this person.
	* The note is assumed to be a totally new note.
	* Creare new note and add a link for this person.
	**********************************************************/
	public function getAddNote($request, $response, $args) {
		$personId = $args['personId'];
		return $this->container->view->render($response, 'Knowledgebase/Person/createNote.twig', compact('personId'));
	}

	public function postAddNote($request, $response, $args) {
		$personId = $args['personId'];

		// The following items need to be validated
		$date = $request->getParam('date');
		$note = $request->getParam('note');

		$privateCheckbox = $request->getParam('private');
		$private = (isset($privateCheckbox));	

		// Create a new note
		$addressId = \App\Models\Note::insertGetId([
							'date' => $date,
							'note' => $note
						]);

		// Create a new note-link
		\App\Models\NoteLink:: insert([
							'person_id' => $personId,
							'note_id' => $noteId,
							'private' => $private
						]);

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('New', 'Note', $personId);
		}

		$this->container->flash->addMessage('info', "A new note added to this person's record.");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));

	}

	/************************************************************************
	* UPDATE
	* Amend an existing note.
	*************************************************************************/
	public function getUpdateNote($request, $response, $args) {
		$personId = $args['personId'];
		$noteId = $args['noteId'];
		
		$medical = \App\Models\Note::find($noteId);
		$medicalLink = \App\Models\NoteLink::where('note_id', '=', $noteId)->first();
		$private = $nodeLink['private'];

		//dump($medical); die();
		
		return $this->container->view->render($response, 'Knowledgebase/Person/updateNote.twig', compact('personId', 'medical', 'private'));
	}

	public function postUpdateNote($request, $response, $args) {
		$personId = $args['personId'];
		$noteId = $args['noteId'];

		// Get all the note-link entries for this note 
		$nodeLinkIds = \App\Models\NoteLink::
						where('note_id', '=', $noteId)
						->get();

		// The following items need to be validated
		$date = $request->getParam('date');
		$note = $request->getParam('note');		

		// Convert date format from database to output format
		$date = date_format(date_create_from_format("Y-m-d", $date), "d/m/Y");						

		\App\Models\Note::
							where('id', '=', $noteId)
							->update([
								'date' => $date,
								'note' => $note,
							]);

		// Update private flag 
		// Get the note-link entry for this note (there should only be one)
		$noteLink = \App\Models\NoteLink::
										where('note_id', '=', $noteId)
										->first();
		// Only update if flag has changed
		if ($noteLink['private'] != $private) {
			\App\Models\NoteLink::
						where('note_id', '=', $noteId)
						->update(['private' => !$private]);
		}

		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Update', 'Note', $personId);
		}

		$this->container->flash->addMessage('info', "Note updated");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	/****************************************************************
	* DELETE
	* Remove note from Addresses and person's link to this note 
	*****************************************************************/

	public function deleteNote($request, $response, $args) {
		$personId = $args['personId'];
		$noteId = $args['noteId'];

		// Delete address link
		\App\Models\NoteLink::
						where('person_id', '=', $personId)
						->where('note_id', '=', $noteId)
						->delete();

		// Delete address
		\App\Models\Note::
						where ('id', '=', $noteId)
						->delete();
		
		// Issue notice if public i.e. not private
		if (!$private) {
			\App\Models\Notice::makeSystemNotice('Delete', 'Note', $personId);
		}

		$this->container->flash->addMessage('info', "Note deleted from this person's record");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));
	}

	
	/***********************************************************************
	* Toggle private status of an address
	************************************************************************/
	public function updatePrivateNote($request, $response, $args) {
		$noteLinkId = $args['noteLinkId'];
		$personId = $args['personId'];
		$p = \App\Models\NoteLink::where('id', $noteLinkId)->value('private');
		\App\Models\NoteLink::
								where('id', '=', $noteLinkId)
								->update(['private' =>  !$p]);
		
		$this->container->flash->addMessage('info', "Note (" . $noteLinkId . ") privacy status changed ");
		return $response->withRedirect($this->container->router->pathFor('person', ['personId'=>$personId]));					

	}


}