<?php
function getUserList($currentgroup='',$courseId,$contextlevel,$context,$limitUsers=15){
	global $USER, $CFG, $DB, $OUTPUT;
	
	$groupmembers = "";
	$groupselect  = "";
	$params = array();

	//Add this to the SQL to show only group users
	if ($currentgroup !== NULL) {
		$groupmembers = ", {groups_members} gm";
		$groupselect = "AND u.id = gm.userid AND gm.groupid = :currentgroup";
		$params['currentgroup'] = $currentgroup;
	}

	$userfields = user_picture::fields('u', array('username'));
	if ($courseId == SITEID or $contextlevel < CONTEXT_COURSE) {  // Site-level
		 
		//Only show if is admin
		if(!checkIfUserIsAdmin()){
			return '';
		}
		 
		$sql = "SELECT $userfields, ul.lastip, MAX(ul.timemodified) AS lastaccess
                      FROM {user} u $groupmembers ,{sessions} ul
                     WHERE u.id = ul.userid AND u.deleted = 0
                     $groupselect
                  GROUP BY $userfields
                  ORDER BY ul.timemodified DESC ";
	} else {
		// Course level - show only enrolled users for now

		//Only show if is teacher or admin
		if(!checkIfUserIsTeacher($courseId) ){
			return '';
		}

		list($esqljoin, $eparams) = get_enrolled_sql($context);
		$params = array_merge($params, $eparams);

		$sql = "SELECT $userfields, ul.lastip, MAX(ul.timemodified) AS lastaccess
                      FROM {sessions} ul $groupmembers, {user} u
                      JOIN ($esqljoin) euj ON euj.id = u.id
                     WHERE u.id = ul.userid
                           AND u.deleted = 0
                           $groupselect
                  GROUP BY $userfields
                  ORDER BY lastaccess DESC";

		$params['courseid'] = $courseId;
	}

	if ($users = $DB->get_records_sql($sql, $params, 0, $limitUsers)) {   // We'll just take the most recent 50 maximum
		foreach ($users as $user) {
			$users[$user->id]->fullname = fullname($user);
		}
	} else {
		$users = array();
	}
	
	return $users;
}

function checkIfUserIsAdmin(){
	if (has_capability('moodle/site:config',context_system::instance()) ) {
		return true;
	}
	return false;
}

function checkIfUserIsTeacher($courseID){
	$context = context_course::instance($courseID);
	if (has_capability('moodle/course:manageactivities',$context) ) {
		return true;
	}
	return false;
}

function deleteAcessUser($sessionKey){
	global $DB;
	$DB->delete_records_select('sessions', "sid='".$sessionKey."'");
}