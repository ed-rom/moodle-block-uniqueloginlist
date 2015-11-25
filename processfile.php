<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_sesskey();
require_login();

$id = optional_param('id', 0, PARAM_INT); // course ID
$courseid = optional_param('courseid', 0, PARAM_INT); // course  ID
$seeall = optional_param('seeall', 0, PARAM_INT); // seeall 


$debug = false;
if ($debug) {
	echo 'Error '.__LINE__.' on file '.__FILE__.'<br />';
	echo 'ID: $id = '.$id.'<br />';
	echo 'course  ID: $course = '.$courseid.'<br />';
	die;
}

if (! $course = $DB->get_record('course', array('id'=>$courseid))) {
	print_error('coursemisconf');
}

$acess=false;
if(checkIfUserIsAdmin()){
	$acess=true;
}else if(checkIfUserIsTeacher($courseid)){
	$acess=true;
}

if(!$acess){
	 print_error('dennyacess', 'block_uniquelogin_list', '', $courseid);
}

//Retirar acesso
$aLogins = $DB->get_records('sessions',array('userid'=>$id));
if($aLogins){
	foreach ($aLogins as $user) {
		deleteAcessUser($user->sid);
	}
}

if($seeall==1){
	$returnurl = new moodle_url('/blocks/uniquelogin_list/list_users.php', array('courseid'=>$courseid));
	redirect($returnurl,get_string('deletesessiondone','block_uniquelogin_list'));
}else{
	$returnurl = ($courseid == SITEID) ? new moodle_url('/index.php') : new moodle_url('/course/view.php', array('id'=>$courseid));
	redirect($returnurl,get_string('deletesessiondone','block_uniquelogin_list'));
}

