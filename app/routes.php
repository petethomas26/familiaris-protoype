<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

$app->get('/', 'HomeController:index')->setName('home');

/* Auth signup, signin, install*/
$app->group('', function() {

	$this->get('/auth/signup', 'AuthController:getSignUp')->setName('auth.signup');

	$this->post('/auth/signup', 'AuthController:postSignUp');

	$this->get('/auth/signin', 'AuthController:getSignIn')->setName('auth.signin');

	$this->post('/auth/signin', 'AuthController:postSignIn');

	$this->get('/auth/install', 'InstallController:getInstall')->setName('auth.install');

	$this->post('/auth/install', 'InstallController:postInstall');

})->add(new GuestMiddleware($container));

/* Auth password change */
$app->group('', function() {

	$this->get('/auth/signout', 'AuthController:getSignOut')->setName('auth.signout');

	$this->get('/auth/password/change', 'PasswordController:getChangePassword')->setName('auth.password.change');

	$this->post('/auth/password/change', 'PasswordController:postChangePassword');

})->add(new AuthMiddleware($container));

/* Auth email change */
$app->group('', function() {

	$this->get('/auth/email/change', 'EmailController:getChangeEmail')->setName('auth.email.change');

	$this->post('/auth/email/change', 'EmailController:postChangeEmail');

})->add(new AuthMiddleware($container));

/* Membership */
$app->get('/membership', 'MembershipController:membership')->setName('membership');

$app->group('', function() {

	$this->get('/membership/member/{id}', 'MembershipController:getMember')->setName('member');

	$this->get('/membership/members', 'MembershipController:getMembers')->setName('members');

	$this->post('/membership', 'MembershipController:postMembership');

	$this->get('/membership/invite', 'MembershipController:invite')->setName('invite');

	$this->post('/membership/invite', 'MembershipController:postInvite');

	$this->get('/membership/sendMessage', 'MembershipController:sendMessage')->setName('sendMessage');

	$this->post('/membership/sendMessage', 'MembershipController:postSendMessage');

	$this->get('/membership/notice', 'MembershipController:notice')->setName('notice');

	$this->post('/membership/notice', 'MembershipController:postNotice')->setName('postNotice');

	$this->get('/membership/findMember', 'MembershipController:getFindMember')->setName('findMember');

	$this->post('/membership/findMember', 'MembershipController:postFindMember')->setName('postFindMember');

	$this->get('/membership/opinion', 'MembershipController:getOpinion')->setName('getOpinion');

	$this->post('/membership/opinion/', 'MembershipController:postOpinion')->setName('postOpinion');

	$this->get('/membership/myMembership', 'MembershipController:getMyMembership')->setName('getMyMembership');

	$this->post('/membership/myMembership/', 'MembershipController:postMyMembership')->setName('postMyMembership');

	$this->get('/membership/inviteChild', 'MembershipController:getInviteChild')->setName('inviteChild');

	$this->post('/membership/inviteChild/', 'MembershipController:postInviteChild')->setName('postInviteChild');

})->add(new AuthMiddleware($container));


/* Knowledgebase */
$app->get('/knowledgebase', 'KnowledgebaseController:knowledgebase')->setName('knowledgebase');

$app->post('/knowledgebase/person', 'KnowledgebaseController:isPerson')->setName('isperson');

$app->group('', function() {

	$this->get('/knowledgebase/person/{personId}', 'KnowledgebaseController:getPerson')->setName('person');

	$this->get('/knowledgebase/remember/{memberId}/{personId}', 'KnowledgebaseController:rememberPerson')->setName('remember');

	$this->get('/knowledgebase/forget/{memberId}/{personId}', 'KnowledgebaseController:forgetPerson')->setName('forget');
	
	$this->get('/knowledgebase/favourites/{memberId}/{personId}', 'KnowledgebaseController:getFavourites')->setName('favourites');

	$this->get('/knowledgebase/people', 'KnowledgebaseController:getPeople')->setName('people');

	$this->get('/knowledgebase/getMyPerson', 'KnowledgebaseController:getMyPerson')->setName('getMyPerson');

	$this->get('/knowledgebase/findPerson', 'KnowledgebaseController:getFindPerson')->setName('findPerson');

	$this->post('/knowledgebase/simpleFindPerson/{page}/{personId}/{who}', 'KnowledgebaseController:findPerson')->setName('simpleFindPerson');

	$this->post('/knowledgebase/getPersonId', 'KnowledgebaseController:getPersonId')->setName('getPersonId');

	$this->post('/knowledgebase/findPerson', 'KnowledgebaseController:postFindPerson')->setName('postFindPerson');

	$this->get('/knowledgebase/createPerson/{personId}', 'KnowledgebaseController:getCreatePerson')->setName('createPerson');

	$this->post('/knowledgebase/createPerson/{personId}', 'KnowledgebaseController:postCreatePerson')->setName('postCreatePerson');

	$this->get('/knowledgebase/createMyPerson', 'KnowledgebaseController:getCreateMyPerson')->setName('createMyPerson');

	$this->get('/knowledgebase/choosePerson', 'KnowledgebaseController:getChoosePerson')->setName('choosePerson');

	$this->get('/knowledgebase/updatePerson', 'KnowledgebaseController:getUpdatePerson')->setName('updatePerson');

	$this->post('/knowledgebase/updatePerson/{personId}', 'KnowledgebaseController:postUpdatePerson')->setName('postUpdatePerson');

	$this->post('/knowledgebase', 'KnowledgebaseController:postPerson');

	$this->post('/knowledgebase/addNickname/{personId}', 'KnowledgebaseController:addNickname')->setName('addNickname');

	$this->get('/knowledgebase/deleteNickname/{personId}/{nicknameId}', 'KnowledgebaseController:deleteNickname')->setName('deleteNickname');

	$this->post('/knowledgebase/addCurrentLastName/{personId}', 'KnowledgebaseController:addCurrentLastName')->setName('addCurrentLastName');
	$this->post('/knowledgebase/addPreviousLastName/{personId}', 'KnowledgebaseController:addPreviousLastName')->setName('addPreviousLastName');
	$this->get('/knowledgebase/deletePreviousLastName/{personId}/{lastNameId}', 'KnowledgebaseController:deletePreviousLastName')->setName('deletePreviousLastName');

})->add(new AuthMiddleware($container));

/* Notices */
$app->group('', function() {
	$this->get('/notice/notices/{set}/{no}/{page}', 'NoticeController:getNotices')->setName('notices');
	$this->post('/notice/notices', 'NoticeController:postNotices')->setName('postNotices');
})->add(new AuthMiddleware($container));


/* Partnerships */
$app->group('', function() {
	$this->get('/partnership/addPartnership/{personId}/{memberId}', 'PartnershipController:getAddPartnership')->setName('addPartnership');
	$this->post('/partnership/addPartnership/{personId}', 'PartnershipController:postAddPartnership')->setName('postAddPartnership');

	$this->get('/partnership/deletePartnership/{personId}/{partnershipId}', 'PartnershipController:deletePartnership')->setName('deletePartnership');

	$this->get('/partnership/updatePartnership/{personId}/{partnershipId}', 'PartnershipController:getUpdatePartnership')->setName('updatePartnership');
	$this->post('/partnership/updatePartnership/{personId}/{partnershipId}', 'PartnershipController:postUpdatePartnership')->setName('postUpdatePartnership');

	$this->get('/partnership/private/{personId}/{partnershipId}', 'PartnershipController:updatePrivate')->setName('updatePrivate');
})->add(new AuthMiddleware($container));

/* Address */
$app->group('', function() {
	$this->get('/address/addAddress/{personId}','AddressController:getAddAddress')->setName('addAddress');
	$this->post('/address/addAddress/{personId}','AddressController:postAddAddress')->setName('postAddAddress');

	$this->get('/address/deleteDomicile/{personId}/{addressId}', 'AddressController:getDeleteAddress')->setName('deleteAddress');

	$this->get('/address/updateAddress/{personId}/{addressId}', 'AddressController:getUpdateAddress')->setName('updateAddress');	
	$this->post('/address/updateAddress/{personId}/{addressId}', 'AddressController:postUpdateAddress')->setName('postUpdateAddress');

	$this->get('/address/private/{personId}/{addressLinkId}', 'AddressController:updatePrivateAddress')->setName('updatePrivateAddress');
})->add(new AuthMiddleware($container));


/* Education */
$app->group('', function() {
	$this->get('/education/addEducation/{personId}','EducationController:getAddEducation')->setName('addEducation');
	$this->post('/education/addEducation/{personId}','EducationController:postAddEducation')->setName('postAddEducation');

	$this->get('/education/deleteEducation/{personId}/{educationId}', 'EducationController:getDeleteEducation')->setName('deleteEducation');

	$this->get('/education/updateEducation/{personId}/{educationId}', 'EducationController:getUpdateEducation')->setName('updateEducation');
	$this->post('/education/updateEducation/{personId}/{educationId}', 'EducationController:postUpdateEducation')->setName('postUpdateEducation');

	$this->get('/education/updatePrivateEducation/{personId}/{educationLinkId}', 'EducationController:updatePrivateEducation')->setName('updatePrivateEducation');

})->add(new AuthMiddleware($container));


/* Medical */
$app->group('', function() {
	$this->get('/medical/addMedical/{personId}','MedicalController:getAddMedical')->setName('addMedical');
	$this->post('/medical/addMedical/{personId}','MedicalController:postAddMedical')->setName('postAddMedical');

	$this->get('/medical/deleteMedical/{personId}/{medicalId}', 'MedicalController:deleteMedical')->setName('deleteMedical');

	$this->get('/medical/updateMedical/{personId}/{medicalId}', 'MedicalController:getUpdateMedical')->setName('updateMedical');
	$this->post('/medical/updateMedical/{personId}/{medicalId}', 'MedicalController:postUpdateMedical')->setName('postUpdateMedical');

	$this->get('/medical/private/{personId}/{medicalLinkId}', 'MedicalController:updatePrivateMedical')->setName('updatePrivateMedical');
})->add(new AuthMiddleware($container));

/* Pastimes */
$app->group('', function() {
	$this->get('/pastime/addPastime/{personId}','PastimeController:getAddPastime')->setName('addPastime');
	$this->post('/pastime/addPastime/{personId}','PastimeController:postAddPastime')->setName('postAddPastime');

	$this->get('/pastime/deleteNote/{personId}/{pastimeId}', 'PastimeController:deletePastime')->setName('deletePastime');

	$this->get('/pastime/updatePastime/{personId}/{pastimeId}', 'PastimeController:getUpdatePastime')->setName('updatePastime');
	$this->post('/pastime/updatePastime/{personId}/{pastimeId}', 'PastimeController:postUpdatePastime')->setName('postUpdatePastime');

	$this->get('/pastime/private/{personId}/{pastimeLinkId}', 'PastimeController:updatePrivatePastime')->setName('updatePrivatePastime');
})->add(new AuthMiddleware($container));

/* Awards */
$app->group('', function() {
	$this->get('/award/addAward/{personId}','AwardController:getAddAward')->setName('addAward');
	$this->post('/award/addAward/{personId}','AwardController:postAddAward')->setName('postAddAward');

	$this->get('/award/deleteAward/{personId}/{awardId}', 'AwardController:deleteAward')->setName('deleteAward');

	$this->get('/award/updateAward/{personId}/{awardId}', 'AwardController:getUpdateAward')->setName('updateAward');
	$this->post('/award/updateAward/{personId}/{awardId}', 'AwardController:postUpdateAward')->setName('postUpdateAward');

	$this->get('/award/private/{personId}/{awardLinkId}', 'AwardController:updatePrivateAAward')->setName('updatePrivateAward');
})->add(new AuthMiddleware($container));

/* Employments */
$app->group('', function() {
	$this->get('/employment/addEmployment/{personId}','EmploymentController:getAddEmployment')->setName('addEmployment');
	$this->post('/employment/addEmployment/{personId}','EmploymentController:postAddEmployment')->setName('postAddEmployment');

	$this->get('/employment/deleteEmployment/{personId}/{employmentId}', 'EmploymentController:deleteEmployment')->setName('deleteEmployment');

	$this->get('/employment/updateEmployment/{personId}/{employmentId}', 'EmploymentController:getUpdateEmployment')->setName('updateEmployment');
	$this->post('/employment/updateEmployment/{personId}/{employmentId}', 'EmploymentController:postUpdateEmployment')->setName('postUpdateEmployment');

	$this->get('/employment/private/{personId}/{employmentLinkId}', 'EmploymentController:updatePrivateEmployment')->setName('updatePrivateEmployment');
})->add(new AuthMiddleware($container));


/* Outputs */
$app->group('', function() {
	$this->get('/output/addOutput/{personId}','OutputController:getAddOutput')->setName('addOutput');
	$this->post('/output/addOutput/{personId}','OutputController:postAddOutput')->setName('postAddOutput');

	$this->get('/output/deleteOutput/{personId}/{outputId}', 'OutputController:deleteOutput')->setName('deleteOutput');

	$this->get('/output/updateOutput/{personId}/{outputId}', 'OutputController:getUpdateOutput')->setName('updateOutput');
	$this->post('/output/updateOutput/{personId}/{outputId}', 'OutputController:postUpdateOutput')->setName('postUpdateOutput');

	$this->get('/output/private/{personId}/{outputLinkId}', 'OutputController:updatePrivateOutput')->setName('updatePrivateOutput');
})->add(new AuthMiddleware($container));


/* Politics */
$app->group('', function() {
	$this->get('/political/addPolitical/{personId}','PoliticalController:getAddPolitical')->setName('addPolitical');
	$this->post('/political/addPolitical/{personId}','PoliticalController:postAddPolitical')->setName('postAddPolitical');

	$this->get('/political/deletePolitical/{personId}/{politicalId}', 'PoliticalController:deletePolitical')->setName('deletePolitical');

	$this->get('/political/updatePolitical/{personId}/{politicalId}', 'PoliticalController:getUpdatePolitical')->setName('updatePolitical');
	$this->post('/political/updatePolitical/{personId}/{politicalId}', 'PoliticalController:postUpdatePolitical')->setName('postUpdatePolitical');

	$this->get('/political/private/{personId}/{politicalLinkId}', 'PoliticalController:updatePrivatePolitical')->setName('updatePrivatePolitical');
})->add(new AuthMiddleware($container));

/* Service */
$app->group('', function() {
	$this->get('/service/addService/{personId}','ServiceController:getAddService')->setName('addService');
	$this->post('/service/addService/{personId}','ServiceController:postAddService')->setName('postAddService');

	$this->get('/service/deleteService/{personId}/{serviceId}', 'ServiceController:deleteService')->setName('deleteService');

	$this->get('/service/updateService/{personId}/{serviceId}', 'ServiceController:getUpdateService')->setName('updateService');
	$this->post('/service/updateService/{personId}/{serviceId}', 'ServiceController:postUpdateService')->setName('postUpdateService');

	$this->get('/service/private/{personId}/{serviceLinkId}', 'ServiceController:updatePrivateService')->setName('updatePrivateService');
})->add(new AuthMiddleware($container));

/* Memory */
$app->group('', function() {
	$this->get('/memory/addMemory/{personId}','MemoryController:getAddMemory')->setName('addMemory');
	$this->post('/memory/addMemory/{personId}','MemoryController:postAddMemory')->setName('postAddMemory');

	$this->get('/memory/deleteMemory/{personId}/{memoryId}', 'MemoryController:deleteMemory')->setName('deleteMemory');

	$this->get('/memory/updateMemory/{personId}/{memoryId}', 'MemoryController:getUpdateMemory')->setName('updateMemory');
	$this->post('/memory/updateMemory/{personId}/{memoryId}', 'MemoryController:postUpdateMemory')->setName('postUpdateMemory');

	$this->get('/memory/private/{personId}/{memoryLinkId}', 'MemoryController:updatePrivateMemory')->setName('updatePrivateMemory');
})->add(new AuthMiddleware($container));

/* Military service */
$app->group('', function() {
	$this->get('/military/addMilitary/{personId}','MilitaryController:getAddMilitary')->setName('addMilitary');
	$this->post('/military/addMilitary/{personId}','MilitaryController:postAddMilitary')->setName('postAddMilitary');

	$this->get('/military/deleteMilitary/{personId}/{militaryId}', 'MilitaryController:deleteMilitary')->setName('deleteMilitary');

	$this->get('/military/updateMilitary/{personId}/{militaryId}', 'MilitaryController:getUpdateMilitary')->setName('updateMilitary');
	$this->post('/military/updateMilitary/{personId}/{militaryId}', 'MilitaryController:postUpdateMilitary')->setName('postUpdateMilitary');

	$this->get('/military/private/{personId}/{militaryLinkId}', 'MilitaryController:updatePrivateMilitary')->setName('updatePrivateMilitary');
})->add(new AuthMiddleware($container));

/* Notes */
$app->group('', function() {
	$this->get('/note/addNote/{personId}','NoteController:getAddNote')->setName('addNote');
	$this->post('/note/addNote/{personId}','NoteController:postAddNote')->setName('postAddNote');

	$this->get('/note/deleteNote/{personId}/{noteId}', 'NoteController:deleteNote')->setName('deleteNote');

	$this->get('/note/updateNote/{personId}/{noteId}', 'NoteController:gettUpdateNote')->setName('updateNote');
	$this->post('/note/updateNote/{personId}/{noteId}', 'NoteController:postUpdateNote')->setName('postUpdateNote');

	$this->get('/note/private/{personId}/{noteLinkId}', 'NoteController:updatePrivateNote')->setName('updatePrivateNote');
})->add(new AuthMiddleware($container));


/* Trees */
$app->get('/tree', 'TreeController:tree')->setName('tree');

$app->group('', function() {

	$this->get('/tree/getFullTree', 'TreeController:getFullTree')->setName('fullTree');

	$this->post('/tree/postAncestorTree', 'TreeController:postAncestorTree')->setName('ancestorTree');

	$this->get('/tree/getMyAncestorTree/{personId}/{personName}', 'TreeController:getMyAncestorTree')->setName('myAncestorTree');

	$this->get('/tree/getAncestorTree', 'TreeController:getAncestorTree')->setName('ancestors');

	$this->get('/tree/getDescendantTree', 'TreeController:getDescendantTree')->setName('descendants');

	$this->get('/tree/getUnlinkedPeople', 'TreeController:getUnlinkedPeople')->setName('unlinked');

})->add(new AuthMiddleware($container));

/* Photos */
$app->group('', function() {
	$this->post('/photo/postUploadPhoto/{personId}', 'PhotoController:postUploadPhoto')->setName('uploadPhoto');

	$this->get('/photo/getShowPhoto/{personId}', 'PhotoController:getShowPhoto')->setName('showPhoto');

	$this->post('/photo/postShowPhoto', 'PhotoController:postShowPhoto')->setName('postShowPhoto');

	$this->get('/photo/getDeletePhoto/{personId}/{photoId}', 'PhotoController:getDeletePhoto')->setName('deletePhoto');
})->add(new AuthMiddleware($container));




/* Voting */
$app->group('', function() { 
	$this->get('/vote/getVotings', 'VoteController:getVotings')->setName('getVotings');
	$this->post('/vote/createStatement', 'VoteController:createStatement')->setName('createStatement');
})->add(new AuthMiddleware($container));

/* Administration */
$app->group('', function() {
	$this->get('/administration', 'AdminController:administration')->setName('administration');

	$this->post('/adminisration/contactAdmin', 'AdminController:contactAdmin')->setName('contactAdmin');

	$this->get('/adminisration/monitorQueries/{view}', 'AdminController:monitorQueries')->setName('monitorQueries');

	$this->get('/adminisration/viewAllQueries', 'AdminController:viewAllQueries')->setName('viewAllQueries');

	$this->get('/adminisration/viewWaitingQueries', 'AdminController:viewWaitingQueries')->setName('viewWaitingQueries');

	$this->get('/administration/respondToQuery/{queryId}', 'AdminController:respondToQuery')->setName('respondToQuery');
	$this->post('/administration/sendresponse', 'AdminController:sendresponse')->setName('sendResponse');

	$this->get('/administration/pickupQuery/{queryId}', 'AdminController:pickupQuery')->setName('pickUpQuery');
	$this->get('/administration/releaseQuery/{queryId}', 'AdminController:releaseQuery')->setName('releaseQuery');
	$this->get('/administration/discardQuery/{queryId}', 'AdminController:discardQuery')->setName('discardQuery');
	$this->get('/administration/reinstateQuery/{queryId}', 'AdminController:reinstateQuery')->setName('reinstateQuery');


	$this->get('/administration/getSetAdministrator/{ok}', 'AdminController:getSetAdministrator')->setName('setAdministrator');
	$this->post('/administration/postSetAdministrator', 'AdminController:postSetAdministrator')->setName('postSetAdministrator');

	$this->get('/adminisration/checkSetAdministrator', 'AdminController:checkSetAdministrator')->setName('checkSetAdministrator');
	//$this->get('/Administration/suspendmember', 'AdminController:getSuspendMember')->setName('suspendMember');
	
	$this->post('/Administration/suspendMember', 'AdminController:postSuspendMember')->setName('postSuspendMember');

	$this->post('/Administration/reinstateMember', 'AdminController:postReinstateMember')->setName('postReinstateMember');

	$this->post('/Administration/backupDatabase', 'AdminController:postBackupDatabase')->setName('postBackupDatabase');

	$this->post('/Administration/rollbackDatabase', 'AdminController:postRollbackDatabase')->setName('postRollbackDatabase');

	$this->get('/adminisration/findOrphans', 'AdminController:getFindOrphans')->setName('findOrphans');

	$this->get('/adminisration/showOrphans', 'AdminController:showOrphans')->setName('showOrphans');

	$this->post('/adminisration/removeOrphans', 'AdminController:removeOrphans')->setName('removeOrphans');

	$this->get('/adminisration/examineActivity', 'AdminController:examineActivity')->setName('examineActivity');

	
})->add(new AuthMiddleware($container));

/* Developer */
/*
$app->group('', function() {
	$this->get('/developer/getCompareNames', 'DeveloperController:getCompareNames')->setName('compareNames');

	$this->post('/developer/postCompareNames', 'DeveloperController:postCompareNames')->setName('postCompareNames');	
	
})->add(new AuthMiddleware($container));
*/

//$app->get('/about', 'AboutController:getAbout')->setName('about');

//$app->get('/guide', 'GuideController:getGuide')->setName('guide');

//$app->get('/help', 'HelpController:getHelp')->setName('help');

//$app->get('/contact', 'ContactController:getContact')->setName('contact');


