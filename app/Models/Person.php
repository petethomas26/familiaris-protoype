<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use DateTime;

class Person extends Model {
	protected $table = 'people';

	protected $fillable = [
		'title',
		'first_name',
		'middle_name',
		'last_name',
		'alt_last_name',
		'photo_name',
		'photo_extension',
		'date_of_birth',
		'unsure_date_of_birth',
		'date_of_death',
		'unsure_date_of_death',
		'birth_location',
		'death_location',
		'nationality',
		'gender',
		'nat_ins_no',
		'passport_no',
		'father',
		'mother',
		'current_partner',
		'partnership',
		'address',
		'created_by',
	];


	 protected $result = [
		'people' => [],
		'similarity' => 0.0,
		'message' => '',
		'status' => []
	];

	public function fullName() {
		$firstName = $this->first_name;
		$middleName = $this->middle_name;
		$lastName = $this->last_name;
		if (isset($middleName)) {
			return $firstName . ' ' . $middleName . ' ' . $lastName;
		} else {
			return $firstName . ' ' . $lastName;
		}
	}

	public function shortName() {
		$firstName = $this->first_name;
		$lastName = $this->last_name;
		return $firstName . ' ' . $lastName;
	}

	public function isChild() {
		$birthDate = date_create($this->date_of_birth);
		$now = new DateTime();
		$diff = date_diff($now, $birthDate);
		return ($diff->y <16);
	}

// Is the current user (member), whose person id is currently in S_SESSION['person'] 
// either the mother or the father of this child?
	public function isChildOfMember() {
		$personId = $_SESSION['person'];
		$motherId = $this->mother;
		$fatherId = $this->father;
		return ($personId === $motherId or $personId === $fatherId);
	}


// Gets the year from a date in the format Y-m-d
	public function getYear($date) {
		if ($date === "" or $date === null) {
			return null;
		} else {
			$d = explode('-', $date);
			$year = $d[0];
			return $year;
		}
	}

	
	/*****************************************************************************
	 * Finds as many people in knowledgebase that match given criteria
	 * $firstName can match with either a person's full first name or nickname
	 * An unspecified $gender ('u') matches with any gender ('m','f','o')
	 * $yearOfBirth can match any year within a range (currently fixed at 10)
	 * The initial algorithm looks for exact matches on names
	 * Assumes parameters have been validated.
	 *
	 * Partial matches with $firstName and $lastName are supported: a similarity 
	 *     value is returned with matches ordered on similarity
	 * Only those people with a similarity measure greater than 'threshold' 
	 *     are returned.
	 * A similarity measure (and threshold) is a value in the range [0,1]
	 ****************************************************************************/

	/****************************************************************************
	* There are two algorithms for finding people:
	*   one looks for exact matches between names (no threshold)
	*   the other, more expensive, looks for partial matches (with threshold)
	* ***************************************************************************/

	/********************************************************************************
	* The following routine works through the daatabase looking for first names 
	* and last names that are similar (above threshold) to the given names.
	* The given first name is compared with both first names and nicknames.
	* The given last name is compared with both last names and alternative last names.
	* The same comparison algorithm is used for both first names and lastnames.
	* *******************************************************************************/
	public function findSimilarPeople($firstName, $lastName, $threshold) {
		// Initialize local vars
		$resultIds = [];
		// Get names of all people in database
		$people = \App\Models\Person::select('id', 'first_name', 'nickname', 'last_name')->get();

		// Change to array for processing
		$persons = $people->toArray();

		// Extract the ids of those people with a matching first name or nickname and last name
		foreach($persons as $person) {
			if ((Person::nameSimilarity($firstName, $person['first_name'], $threshold) or
				Person::nameSimilarity($firstName, $person['nickname'], $threshold)) and
				Person::nameSimilarity($lastName, $person['last_name'], $threshold)) {
				$resultIds[] = $person['id'];
			}
		}

		// Fetch those people from database with found ids
		$results = [];
		foreach ($resultIds as $id) {
			$results[] = \App\Models\Person::find('id');
		};

		return $results;
	}

	/*******************************************************************
	* Extract all people from database whose first name or nickname 
	* exactly matches the given first name and whose last_name exactly 
	* matches the given last name.
	* *****************************************************************/
	public function findPeopleExactly($firstName, $nickname, $lastName) {

		// Find all people in knowledgebase with first name matching either $firstName or $nickname
		
		$people = \App\Models\Person::
					where(function($query) use ($firstName, $nickname) {
					$query->where('first_name', '=', $firstName)
							->orWhere('first_name', '=', $nickname);
					})->get();
	
		// Find all people in the db with nickname matching either $firstName or $nickname
		$nicknames = \App\Models\Nickname::
						where(function($query) use ($firstName, $nickname) {
						$query->where('name', '=', $firstName)
								->orWhere('name', '=', $nickname);
						})->get();

		// Combine people with matching nickname to those with matching first name
		// to give set of people whose first name or nickname matches those required
		// Avoid duplication
		foreach ( $nicknames as $nickname) {
			$person = \App\Models\Person::find($nickname['person_id']);
			$found = false;
			foreach($people as $peep) {
				if ($peep['id'] === $person['id']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$people->push($person);
			}
		}
		
		// Convert to array
		$persons = $people->toArray();

		if (count($persons) === 0) {
			$result['people'] = null;
			$result['message'] = "No-one with that first name found in knowledgebase.";
			$result['similarity'] = 1.0;
			$result['criteria'] = null;
			$result['status'] = 'none'; // no people found
			return $result;
		}

		$result['criteria'][] = 'first/nick name';

		// Work through $persons to find those with last name or alt last name matching $lastName
		$pers = [];
		foreach($persons as $person) {
			if ($person['last_name'] === $lastName) {
				$pers[] = $person;
			} else {
				$pid = $person['id'];
				$names = \App\Models\LastName::
						where('person_id', '=', $pid)
						->get();
						
				foreach ($names as $name) {
					if ($name['name'] === $lastName) {
						$pers[] = $person;
						break;
					}
				}
			}
		};


		if (count($pers) === 0) {
			// Some people with matching first names were found; but not matching last names
			$result['people'] = null;
			$result['message'] = "No-one with that last name found in knowledgebase.";
			$result['similarity'] = 1.0;
			$result['status'] = 'none';
			return $result;
		}

		// At least one person has been found

		$result['people'] = $pers;
		$result['message'] = "One or more people found with matching names";
		$result['similarity'] = 1.0;
		$result['criteria'][] = "last name";
		$result['status'] = 'many-all';
		return $result;
	}


	/*****************************************************************
	* Finds people whose names match exactly
	* Status = result of the search:
	* 	none = no-one found
	* 	one-all = exactly one person found matching all criteria
	* 	many-all = more than one person found matching all criteria
	* 	one-some = exactly one person found matching some criteria
	* 	many-some = more than one person matching some criteria
	* ****************************************************************/
	
	public function findPeople($firstName, $nickname, $lastName, $yearOfBirth, $placeOfBirth, $gender, $threshold) {

		// Find all people in knowledgebase with first name matching either $firstName or $nickname
		// and with last name matching either $lastname or  alt last name
		$result = Person::findPeopleExactly($firstName, $nickname, $lastName);

		if ($result['status'] === 'none') {
			return $result;
		}

		// The number of criteria matched against (so far, first name and last name)
		// 'status' contains the names of criteria successfully matched
		$criteria = 2;
			

		// Refine results using year of birth
		// Year of birth is an essential criterion
		// Can match within 5 years of $yearOfBirth
		$persons = [];

		foreach($result['people'] as $person) {
			$dob = $person['date_of_birth'];
			$yob = date_parse_from_format('Y-m-d', $dob)['year'];
			$diff = abs(intval($yob) - intval($yearOfBirth));
			$maxDiff = ($person['unsure_date_of_birth']) ? 15 : 5;
			if ($diff <= $maxDiff) {
				$persons[] = $person;
			}
		};
		
		if (!empty($persons)) {
			$result['people'] = $persons;
			$result['criteria'][] = "year of birth";
		}
		$criteria++;
		
		// Refine results using place of birth, if any
		// If the result is zero people, revert to previous set of results
		if (!empty($placeOfBirth)) {
			$criteria++;
			$persons = [];
			foreach($result['people'] as $person) {
				if ($person['birth_location'] == $placeOfBirth or $person['unsure_place_of_birth']) {
					$persons[] = $person;
				}
			};
			
			if (!empty($persons)) {
				$result['people'] = $persons;
				$result['criteria'][] = "place of birth";
			} 
		} ;



		// Refine results using gender, if any
		if (!empty($gender)) {
			$criteria++;
			$persons = [];
			foreach($result['people'] as $person) {
				if ($person['gender'] == $gender or $person['gender'] == 'unknown') {
					$persons[] = $person;
				}
			};

			if (!empty($persons)) {
				$result['people'] = $persons;
				$result['criteria'][] = "gender";
			} 
		}


		// One or more matching results found
		$noPeople = count($result['people']);

		if ($criteria == count($result['criteria'])) {
			
			if ($noPeople == 1) {
				$result['message'] = "A person matching all your criteria has been found";
				$result['status'] = 'one-all';
			} else {
				$result['message'] = $noPeople . " people match your criteria";
				$result['status'] = 'many-all';
			}
		} else {
			$matchedCriteria = "";
			foreach ($result['criteria'] as $stat) {
				$matchedCriteria .=  $stat . ', ';
			}
			$matchedCriteria = rtrim($matchedCriteria, ", ");
			if ($noPeople == 1) {
				$result['message'] = "No exact matches. This person matches some of your criteria (" . $matchedCriteria . ")";
				$result['status'] = 'one-some';
			} else {
				$result['message'] = "No exact matches. " . $noPeople . " people match some of your criteria (" . $matchedCriteria . ")";
				$result['status'] = 'many-some';
			}
		};


		return $result;

	}	

	/***************************************************************
	* Similarity of names using different algorithms
	* *************************************************************/

	private function preProcessName($name) {
		// convert to lowercase after trimming white space at both ends
		$name = strtolower(trim($name));
		// names can have hyphens - ignore them
		// remove hyphens from both names
		$name = str_replace('-', '', $name);
		return $name;
	}

	public function startSim($name1, $name2) {
		// Pre-process names
		$name1 = Person::preProcessName($name1);
		$name2 = Person::preProcessName($name2);
		// make name1 the shorter name
		if (strlen($name1) > strlen($name2)) {
			$temp = $name1;
			$name1 = $name2;
			$name2 = $temp;
		};
		// convert to character arrays
		$array1 = str_split($name1);
		$array2 = str_split($name2);
		// If the length of $array1 is zero, return a similarity of 0
		$shorterLength = count($array1);
		if ($shorterLength === 0) {
			return 0.0;
		}
		// loop through shorter name
		// count number of characters at start of name1 that match the characters at the start of name2
		$n = 0;
		for ($i=0; $i<$shorterLength; $i++) {
			if ($array1[$i] !== $array2[$i]) {
				break;
			}
			$n++;
		}
		// Compute simple similarity measure
		// similarity = number of characters in common at start of names divided by length of smaller name
		$startSim = $n / $shorterLength;
		return $startSim;
	}

	public function levSim($name1, $name2) {
		// Pre-process names
		$name1 = Person::preProcessName($name1);
		$name2 = Person::preProcessName($name2);
		// Calculate divisor - length of longest name
		$len1 = strLen($name1);
		$len2 = strlen($name2);
		$div = ( $len1 < $len2) ? $len2 : $len1;

		return ($div - levenshtein($name1, $name2)) / $div;
	} 

	public function textSim($name1, $name2) {
		// Pre-process names
		$name1 = Person::preProcessName($name1);
		$name2 = Person::preProcessName($name2);
		// Calculate divisor - length of longest name
		$len1 = strLen($name1);
		$len2 = strlen($name2);
		$div = ( $len1 < $len2) ? $len2 : $len1;

		return ($div - similar_text($name1, $name2)) / $div;
	} 

	public function metaphoneSim($name1, $name2) {
		// Pre-process names
		$name1 = Person::preProcessName($name1);
		$name2 = Person::preProcessName($name2);
		// Calculate metaphone key for each name
		$mp1 = metaphone($name1);
		$mp2 = metaphone($name2);
		// Calculate divisor - length of longest name
		$len1 = strLen($mp1);
		$len2 = strlen($mp2);
		$div = ( $len1 < $len2) ? $len2 : $len1;
		// Compare metaphones using levenshtein distance
		return ($div - levenshtein($mp1, $mp2)) / $div;
	} 

	public function nameSimilarity($name1, $name2, $threshold) {
		// threshold must be in range 0..1
		if ($threshold >= 0 and $threshold <= 1.0) {
			$startSim = Person::startSim($name1, $name2);
			$metaphoneSim = Person::metaphoneSim($name1, $name2);
			return ($startSim >= $threshold) or ($metaphoneSim >= $threshold);
		} else {
			return false;
		}
	}



}
