<?php

namespace App\Controllers\Vote;

use App\Controllers\Controller;

use App\Models\Member;

use App\Models\Vote;

use Respect\Validation\Validator as v;

class VoteController extends Controller {

public function getVotings($request, $response) {
	$memberIds = \App\Models\Member::
							pluck('id');

	$votings = [];

	foreach ($memberIds as $memberId) {
		$votes = \App\Models\Vote::
								where('member_id', '=', $memberId)
								->get();

		$qString = "";
		$separator = ", ";
		if (isset($votes)) {
			foreach($votes as $vote) {
				$qString = $qString . $vote['opinion_id'] . $separator;
			};
			$qString = substr($qString, 0, -2);
			
		}
		//dump($memberId, $qString);
		if ($qString !=="") {
			$votings[$memberId] = $qString;
		};
		
	};
	//dump($votings);

	return $this->container->view->render($response, 'Vote/votesCast.twig', compact('votings'));

}

public function createStatement($request, $response) {
	$newStatement = $request->getParam('statement');
	$endDate = $request->getParam('endDate');
	$votesThreshold = $request->getParam('votesThreshold');

	// Add statement to Opinions
	\App\Models\Opinion::insert([
			'statement' => $newStatement,
			'end_date' => $endDate,
			'votes_threshold' => $votesThreshold
		]);

	$this->container->flash->addMessage('info', "The statement has been added to the Opinionaire.");
	return $response->withRedirect($this->container->router->pathFor('administration'));
}


}