<?php

namespace App\Controllers\Notice;

use App\Controllers\Controller;

use App\Models\Notice;

use App\Models\Person;

use Respect\Validation\Validator as v;

class NoticeController extends Controller {

	

public function getNotices($request, $response, $args) {
	$set = $args['set'];
	$no = $args['no'];
	$page = $args['page'];

	$listLength = 10;

	if ($set == 0) {
		$no = \App\Models\Notice::count();
	}
	$left = $no - $set*$listLength; 
	$lim = ($left > $listLength) ? $listLength : $left;
	$off = $no - ($set+1)*$listLength ;
	$off = ($off < 1) ? 0 : $off;
	if ($left > 0) {
		$notices = \App\Models\Notice::offset($off)->limit($lim)->get();
		$nots = [];
		foreach ($notices as $notice) {
			$entry['date'] = $notice['created_at'];
			$memberId = $notice['member_id'];
			$entry['memberId'] = $memberId;
			if ($memberId === 0) {
				$entry['memberName'] = 'System';
			} else {
				$member = \App\Models\Member::find($notice['member_id']);
				if ($member !== null) {
					$entry['memberName'] = $member['member_name'];
				} else {
					$entry['memberName'] = "unknown member";
				};
			};
			$entry['heading'] = $notice['heading'];
			$entry['notice'] = $notice['notice'];
			$nots[] = $entry;
		};
		$set++;
	};

	return $this->container->view->render($response, 'Notice/notices.twig', compact('nots', 'set', 'no', 'page'));
}

public function postNotices($request, $response, $args) {
	
	
	
	return $response->withRedirect($this->container->router->pathFor('knowledgebase'));
}


}
