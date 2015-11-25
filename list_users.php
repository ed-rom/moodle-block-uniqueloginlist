<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

global $DB, $OUTPUT, $PAGE, $USER, $COURSE, $CFG;

require_once($CFG->libdir.'/tablelib.php');

require_login();

define('DEFAULT_PAGE_SIZE', 20);

$courseid = required_param('courseid', PARAM_INT);
$contextid    = optional_param('contextid', 0, PARAM_INT);                // one of this or
$page         = optional_param('page', 0, PARAM_INT);                     // which page to show
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);  // how many per page


if ($courseid and !$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('no_course', 'block_uniquelogin_list', '', $courseid);
}

$context = context_course::instance($courseid);


$acess=false;
if(checkIfUserIsAdmin()){
	$acess=true;
}else if(checkIfUserIsTeacher($courseid)){
	$acess=true;
}

if(!$acess){
	 print_error('dennyacess', 'block_uniquelogin_list', '', $courseid);
}


$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url('/blocks/uniquelogin_list/list_users.php', array(
    'courseid' => $courseid
));

$PAGE->navbar->add(get_string("blocktitle","block_uniquelogin_list"));
$PAGE->set_title(get_string("blocktitle","block_uniquelogin_list"));
$PAGE->set_heading(get_string("blocktitle","block_uniquelogin_list"));
$PAGE->set_pagetype(get_string("blocktitle","block_uniquelogin_list"));

$PAGE->set_pagelayout('standard');

//Calculate if we are in separate groups
$isseparategroups = ($PAGE->course->groupmode == SEPARATEGROUPS
	&& $PAGE->course->groupmodeforce
	&& !has_capability('moodle/site:accessallgroups', $PAGE->context));

$currentgroup = $isseparategroups ? groups_get_course_group($PAGE->course) : NULL;

$aUsers = getUserList($currentgroup,$courseid,$PAGE->context->contextlevel,$PAGE->context);
echo $OUTPUT->header();
echo('<h3>'.get_string("blocktitle_list","block_uniquelogin_list").'</h3>');

$baseurl = new moodle_url('/blocks/uniquelogin_list/list_users.php', array(
            'contextid' => $context->id,
            'courseid' => $courseid,
            'perpage' => $perpage));

$table = new flexible_table('user-index-participants-'.$courseid);
$table->define_columns(array('userpic','firstname','lastname','lastip', 'lastaccess','deletesession'));
$table->define_headers(array(get_string('userpic'), get_string('firstname'),get_string('lastname'),'IP', get_string('lastaccess'),get_string("deletesession","block_uniquelogin_list")));
$table->define_baseurl($baseurl->out());

if (!isset($hiddenfields['lastaccess'])) {
	$table->sortable(true, 'lastaccess', SORT_DESC);
} else {
	$table->sortable(true, 'firstname', SORT_ASC);
}

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'participants');
$table->set_attribute('class', 'generaltable generalbox');

$table->set_control_variables(array(
TABLE_VAR_SORT    => 'ssort',
TABLE_VAR_HIDE    => 'shide',
TABLE_VAR_SHOW    => 'sshow',
TABLE_VAR_IFIRST  => 'sifirst',
TABLE_VAR_ILAST   => 'silast',
TABLE_VAR_PAGE    => 'spage'
));
$table->setup();

$datestring = new stdClass();
$datestring->year  = get_string('year');
$datestring->years = get_string('years');
$datestring->day   = get_string('day');
$datestring->days  = get_string('days');
$datestring->hour  = get_string('hour');
$datestring->hours = get_string('hours');
$datestring->min   = get_string('min');
$datestring->mins  = get_string('mins');
$datestring->sec   = get_string('sec');
$datestring->secs  = get_string('secs');

if ($aUsers)  {
	foreach ($aUsers as $user) {
		
		$usercontext = context_user::instance($user->id);

		if ($piclink = ($USER->id == $user->id || has_capability('moodle/user:viewdetails', $context) || has_capability('moodle/user:viewdetails', $usercontext))) {
			$profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.$user->firstname.'</a></strong>';
		} else {
			$profilelink = '<strong>'.fullname($user).'</strong>';
		}
		
		if ($user->lastaccess) {
			$lastaccess = format_time(time() - $user->lastaccess, $datestring);
		} else {
			$lastaccess = $strnever;
		}
		
		$data = array();
		$data[] = $OUTPUT->user_picture($user, array('size' => 35, 'courseid'=>$course->id));
        $data[] = $profilelink;
        $data[] = $user->lastname;
        $data[] = $user->lastip;
        $data[] = $lastaccess;
        
        $anchortagcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/delete') . '" alt="'. get_string("deletesession","block_uniquelogin_list") .'" />';
		$anchortag = '<a href="'.$CFG->wwwroot.'/blocks/uniquelogin_list/processfile.php?id='.$user->id.'&courseid='.$courseid.'&seeall=1&sesskey=' . sesskey().'" title="'.get_string("deletesession","block_uniquelogin_list").'">'.$anchortagcontents .'</a>';
        $data[] = '<div class="message">'.$anchortag.'</div>';
        
        $table->add_data($data);
	}
}

$table->print_html();


echo $OUTPUT->footer();
