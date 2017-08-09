<?php

namespace App\Controllers\Administration;



use App\Controllers\Controller;

use App\Models\Member;

use App\Models\Person;

use Respect\Validation\Validator as v;

use Illuminate\Database\Eloquent\ModelNotFoundException;


use Illuminate\Database\Schema\Builder as Schema;
use Illuminate\Database\Capsule\Manager as Capsule;
use Doctrine\DBAL\Driver\PDOMySql\Driver;

class AdminController extends Controller {

	public function administration($request, $response) {
		return $this->container->view->render($response, 'Administration/administration.twig');
	}

	public function checkSetAdministrator($request, $response) {
		$ok = '1';
		//return $this->container->view->render($response, 'Administration/setAdministrator.twig', );
		return $response->withRedirect($this->container->router->pathFor('setAdministrator', ['ok'=>$ok]));
	}

	public function getSetAdministrator($request, $response) {
		$ok = '0';
		return $this->container->view->render($response, 'Administration/setAdministrator.twig', compact('ok'));
	}

	public function postSetAdministrator($request, $response) {
		$currentYear = date('Y');
		$validation = $this->container->validator->validate($request, [
			'firstName' => v::notEmpty()->alpha('-'),
			'lastName' => v::notEmpty()->alpha('-'),
			'yearOfBirth' => v::intVal()->between(0, $currentYear),
			'memberNo' => v::positive()
		]);

		if ($validation->failed()) {
			$this->container->flash->addMessage('info', "Validation failed");
			return $response->withRedirect($this->container->router->pathFor('setAdministrator', ['ok'=>'0']));
		};

		$member = \App\Models\Member::
						find($request->getParam('memberNo'));
		
		if ($member === null) {
			$this->container->flash->addMessage('info', "No member with that member number");
			return $response->withRedirect($this->container->router->pathFor('setAdministrator', ['ok'=>'0']));
		};

		// Get member's person
		$personId = $member['my_person_id'];
		// Find (all) people matching given names and date of birth
		$firstName = $this->standadizeName($request->getParam('firstName'));
		$lastName = $this->standardize($request->getParam('lastName'));
		$result = Person::findPeople($firstName, $lastName, $request->getParam('yearOfBirth'), null, null, 100);
		$noOfPeople = count($result['people']);

		if ($noOfPeople != 1) {
			$this->container->flash->addMessage('info', "Could not find the member requested");
			return $response->withRedirect($this->container->router->pathFor('setAdministrator', ['ok'=>'0']));
		};

		// Check result person against input data
		if ($result['people'][0]['id']  !== $personId) {
			$this->container->flash->addMessage('info', "Member's details do not match those of the member with the given member number");
			return $response->withRedirect($this->container->router->pathFor('setAdministrator', ['ok'=>'0']));
		};
			
		\App\Models\Member::where('id', '=', $member['id'])
							->update(['status'=>2]);
							
		$this->container->flash->addMessage('info', "Member is now an administrator");
		return $response->withRedirect($this->container->router->pathFor('administration'));
		
	}

	public function postSuspendMember($request, $response) {
		$memberId = $request->getParam('memberId');

		try {
			$member = \App\Models\Member::findOrFail($memberId);
		}
		catch (ModelNotFoundException $e) {
			$this->container->flash->addMessage('info', "Unknown Member: no-one has been suspended.");
			return $response->withRedirect($this->container->router->pathFor('administration'));
		}

		if ($member['status'] == 4) {
			$this->container->flash->addMessage('info', "This Member has already been suspended.");
			return $response->withRedirect($this->container->router->pathFor('administration'));
		}

		\App\Models\Member:: where('id', '=', $memberId)
							->update(['status' => 4]);

		$this->container->flash->addMessage('info', "Member ". $memberId . " is now suspended");
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	public function postReinstateMember($request, $response) {
		$memberId = $request->getParam('memberId');

		try {
			$member = \App\Models\Member::findOrFail($memberId);
		}
		catch(ModelNotFoundException $e) {
			$this->container->flash->addMessage('info', "Unknown Member: no-one has been reinstated.");
			return $response->withRedirect($this->container->router->pathFor('administration'));
		}

		if ($member['status'] != 4) {
			$this->container->flash->addMessage('info', "This Member has not been suspended.");
			return $response->withRedirect($this->container->router->pathFor('administration'));
		}

		//Anyone who has been suspended, including administrators, can only be reinstated as ordinary members
		\App\Models\Member:: where('id', '=', $memberId)
							->update(['status' => 1]);


		$this->container->flash->addMessage('info', "Member ". $memberId . " has been reinstated");
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}



	/**********************************************************************************************
	* In this implementation there is a single backup of each table
	* Later implementations may keep two backups (grandfather-father-son)
	* *********************************************************************************************/
	public function postBackupDatabase($request, $response) {
		// Get list of all tables in data base
		// The main database tables have no 'extension' 
		// The backup tables have the extension 'bak'
		
		// get names of all tables in the database
		$tables = $this->container->db->connection('default')->select('SHOW TABLES');

		// Backup each table, one at a time
		foreach ($tables as $table) {
			$name = $table->Tables_in_familiaris;
			// Don't backup a backup table
			if (strrchr($name, '_') === '_bak') continue;
			$backupName = $name . '_bak';
			
			// If no current backup, create empty backup table
			// else empty the current backup
			if (! $this->container->db->schema()->hasTable($backupName)) {
				// Create a new backup table
				// Get names of columns of main table
				$columns = $this->container->db->schema()->getColumnListing($name);
				// Create backup table with same column names and column types as main table
				$this->container->db->schema()->create($backupName, function($table) use($name, $columns){
					foreach ($columns as $column) {
						$type = $this->container->db->schema()->getColumnType($name, $column);
						switch ($type) {
							case "increments":
								$table->increments($column);
								break;
							case "integer":
								$table->integer($column);
								break;
							case "string":
								$table->string($column, 50);
								break;
							case "text":
								$table->text($column, 50);
								break;
							case "smallint":
								$table->tinyInteger($column);
								break;
							case "boolean":
								$table->tinyInteger($column);
								break;
							case "date":
								$table->date($column)->nullable();
								break;
							case "datetime":
								$table->timestamp($column);
								break;
							default:
						}
					}

				});
			} else {
				$this->container->db->connection('default')->table($backupName)->truncate();
			}

			// Copy items from main table to backup table
			$rows = $this->container->db->connection('default')->table($name)->get();
			foreach ($rows as $row) {
				$row = (array)$row;
				$this->container->db->connection('default')->table($backupName)->insert($row);
			}
			
		} // end of loop for each table

		// It is possible that a backup of a links table can become inconsistent with its related table
		// e.g. there is a link to a non-existent address because the address was deleted before the link 
		// could be deleted.
		// Perform consistency check and remove any link that points to a non-existent item
		// 
		// Is it possible that the extraneous link should point to an item but the item had not been inserted?
		// 
		// [Could also check whether any item is an orphan (no link to it)  and delete it - expensive operation]
		
		if ($this->linkConsistencyCheck()) {
			$mes = "no inconsistencies found";
		} else {
			$mes = "inconsistencies removed";
		}

		$message = "Backup of database completed with " . $mes;
		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	public function postRollbackDatabase($request, $response) {
		// get names of all tables in the database
		$tables = $this->container->db->connection('default')->select('SHOW TABLES');
		// Roll back each table, one at a time
		// If there is a backup available update main table with contents of backup
		foreach ($tables as $table) {
			$name = $table->Tables_in_familiaris;
			// Don't rollback a main table 
			if (strrchr($name, '_') !== '_bak') continue;
			$backupName = $name;
			$name = stristr($name, '_bak', true) ;
			$this->container->db->connection('default')->table($name)->truncate();
			$rows = $this->container->db->connection('default')->table($backupName)->get();
			foreach ($rows as $row) {
				$row = (array)$row;
				$this->container->db->connection('default')->table($name)->insert($row);
			}
		}

		$this->container->flash->addMessage('info', "Rollback of Database completed");
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	// Perform consistency check and remove any link that points to a non-existent item (in backup table)
	protected function linkConsistencyCheck() {
		$result = true;
		// Work through all backup tables looking for all link tables
		$tables = $this->container->db->connection('default')->select('SHOW TABLES');
		foreach ($tables as $table) {
			$name =  $table->Tables_in_familiaris;
			if (strrchr($name, '_') === '_bak') {
				$name = stristr($name, '_bak', true) ;
				if (strrchr($name, '_') === '_links') {
					$linksName = $name;
					$name = stristr($linksName, '_links', true);
					$links = $this->container->db->connection('default')->table($linksName)->get();
					$columnName = $name . '_id';
					$removals = [];
					
					foreach ($links as $link) {
						$id = $link->$columnName;
						// Is there an entry in the table for this link?
						$item = $this->container->db->connection('default')->table($name)->where('id', '=', $id)->first();
						if ($item === null) {
							// Missing item, remember link for later removal
							$removals[] = $link->id;
						} 
					}
					
					// remove all removals from links table
					if (count($removals) > 0) {
						
						foreach ($removals as $removal) {
							$this->container->db->connection('default')->table($linksName)->where('id', '=', $removal)->delete();
						}
						$result = false;
					} 
				}
			}

		} // end of tables loop
		
		return $result;
	}

	// Check whether any item is an orphan (no link to it)  and delete it - expensive operation
	protected function findOrphans() {
		
		// Work through all backup tables looking for all link tables
		$tables = $this->container->db->connection('default')->select('SHOW TABLES');
		$index = 0;
		foreach ($tables as $table) {
			$name =  $table->Tables_in_familiaris;
			// Is the table a backup table?
			if (strrchr($name, '_') === '_bak') {
				// Get the name of the associated main table
				$name = stristr($name, '_bak', true);
				// Is the table a links table
				if (strrchr($name, '_') === '_links') {
					$linksName = $name;
					$name = stristr($linksName, '_links', true);
					// Get all links for this table
					$links = $this->container->db->connection('default')->table($linksName)->get();
					$columnName = $name . '_id';
					//dump($name, $linksName);
					// get list of all item ids from the main (backup) table
					$ids = $this->container->db->connection('default')->table($name)->pluck('id');
					
					foreach ($ids as $id) {
						$item = $this->container->db->connection('default')->table($linksName)->where($columnName, '=', $id)->first();
						if ($item === null) {
							
							$result[$index][0] = $id;
							$result[$index][1] = $name;
							$index++;
						} 
					}
				}
			}
		}
		return $result;
	}

	public function getFindOrphans($request, $response) {

		$result = $this->findOrphans();
		
		if (count($result) > 0) {
			return $this->container->view->render($response, 'Administration/showOrphans.twig', ['orphans'=> $result]);
		};

		$this->container->flash->addMessage('info', "No unreferenced items found in database");
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	public function removeOrphans($request, $response, $args) {
		$orphans = $request->getParam('orphans');
		
		$orphs = explode(";", $orphans);
		foreach ($orphs as $orph) {
			$orf = explode(',', $orph);
			$name = $orf[1];
			$id = $orf[0];
			
			$this->container->db->connection('default')->table($name)->delete($id);
		}
		
		$count = count($orphs);
		$message = $count . " unreferenced item" . (($count > 1) ? 's' : '') . " removed from database";
		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	// Outputs system usage log
	public function examineActivity($request, $response) {
		// get the log (from some given date)
		$log = \App\Models\Log::get();
		return $this->container->view->render($response, 'Administration/examineActivity.twig', compact('log'));
	}

	public function contactAdmin($request, $response) {
		// Find administrators
		$administrators = \App\Models\Member::where('status', '=', '2')->get();
		
		$admins = $administrators->toArray();
		foreach($admins as $administrator) {
			$to[] = $administrator['email'];
		};

		$subject = $request->getParam('subject');
		$message = $request->getParam('message');
		$memberId = $_SESSION['member'];
		$member = \App\Models\Member::find($memberId);
		$from = $member['email'];
		

		if (isset($member)) {

			if ($this->mailer($from, $to, $subject, $message)) {
				$message ="Message sent";
			} else {
				$message = "Problem sending invitation; check email address and try again later.";
			}

		} else {
			$message = "Unknown member reference.";
		}

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('membership'));
	}

/***********************************************************************************************
* status = 0 : waiting - not picked up
* status = 1 : picked up - being dealt with
* status = 2 : response sent
* status = 3 : discarded`
* status = 4 : error; needs to be re-sent
* **********************************************************************************************/

	public function monitorQueries($request, $response, $args) {
		// Obtains `this administrators outstanding queries`
		
		$me = $_SESSION['member'];
		$queries = \App\Models\Query::where('status', '=', 'picked up')
									->where('administrator', '=', $me)
									->get();
		
		$view = $args['view'];
		
		return $this->container->view->render($response, 'Administration/monitorQueries.twig', compact('queries', 'view'));
	}

	public function sendResponse($request, $response) {
		$id = $request->getParam('id');
		$to[] = $request->getParam('email');
		$from = "admin@familaris.uk";
		$subject = $request->getParam('subject');
		$message = $request->getParam('message');

		if ($this->mailer($from, $to, $subject, $message)) {
			$message ="Message sent";
			\App\Models\Query::where('id', '=', $id)
								->update(['status'=> 'response sent']);
		} else {
			$message = "Problem sending response; check email address and try again later.";

			\App\Models\Query::where('id', '=', $query['id'])
								->update(['status'=> 'response not sent']);
		}

		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

// Obtains all queries that this administrator is currently dealing with (picked up)
	public function viewWaitingQueries($request, $response) {
		$me = $_SESSION['member'];
		$queries = \App\Models\Query::where('status', '=', 'waiting')
									->where('administrator', '=', $me)
									->get();
		$view = "waiting";
		return $this->container->view->render($response, 'Administration/monitorQueries.twig', compact('queries', 'view'));
	}

	public function viewAllQueries($request, $response) {
		// Obtains all queries
		
		$queries = \App\Models\Query::get();
		$view = "all";
		return $this->container->view->render($response, 'Administration/monitorQueries.twig', compact('queries', 'view'));
	}

	public function respondToQuery($request, $response, $args) {
		$queryId = $args['queryId'];
		$query = \App\Models\Query::find($queryId);
		return $this->container->view->render($response, 'Administration/queryResponder.twig', compact('query'));
	}

	// To change status of query and show which administrator is dealing with the query
	public function pickUpQuery($request, $response, $args) {
		$queryId = $args['queryId'];
		$administratorId = $_SESSION['member'];
		$status = "picked up";
		\App\Models\Query::where('id', '=', $queryId)
							->update(['status'=> $status, 'administrator' => $administratorId]);
		$message = "You have picked up query No: " . $queryId;
		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('respondToQuery', ['queryId'=>$queryId]));
	}

	// For some reason, this administrator wishes to pass query to someone else; reset status of query
	public function releaseQuery($request, $response, $args) {
		$queryId = $args['queryId'];
		$administratorId = $_SESSION['member'];
		$status = "waiting";
		\App\Models\Query::where('id', '=', $queryId)
							->update(['status'=> $status, 'administrator' => '0']);
		$message = "You have released query No: " . $queryId;
		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('administration'));
	}

	// This query is a rogue message and no reply will be given
	public function discardQuery($request, $response, $args) {
		$queryId = $args['queryId'];
		$administratorId = $_SESSION['member'];
		$status = "discarded";
		\App\Models\Query::where('id', '=', $queryId)
							->update(['status'=> $status, 'administrator' => $administratorId]);
		$message = "You have discarded query No: " . $queryId;
		$this->container->flash->addMessage('info', $message);
		
		return $response->withRedirect($this->container->router->pathFor('monitorQueries'));
	}

	// Changes the status of a query from 'discarded' to 'not yet responded to'
	public function reinstateQuery($request, $response, $args) {
		$queryId = $args['queryId'];
		$query = \App\Models\Query::where('id', '=', $queryId)->first();
		
		if ($query['status'] === 'discarded') {
			$administratorId = $_SESSION['member'];
			$status = "waiting";
			\App\Models\Query::where('id', '=', $queryId)
								->update(['status'=> $status, 'administrator' => $administratorId]);
			$message = "You have reinstated query No: " . $queryId;
			
		} else {
			$message = "This query has not been discarded; no need to reinstate.";
		}
		$this->container->flash->addMessage('info', $message);
		return $response->withRedirect($this->container->router->pathFor('monitorQueries'));
	}
}