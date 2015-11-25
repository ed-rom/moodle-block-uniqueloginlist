<?php

/**
 * This block needs to be reworked.
 * The new roles system does away with the concepts of rigid student and
 * teacher roles.
 */

require_once(dirname(__FILE__) . '/locallib.php');

class block_uniquelogin_list extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_uniquelogin_list');
    }

    function get_content() {
        global $USER, $CFG, $DB, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }
        
        //Calculate if we are in separate groups
        $isseparategroups = ($this->page->course->groupmode == SEPARATEGROUPS
                             && $this->page->course->groupmodeforce
                             && !has_capability('moodle/site:accessallgroups', $this->page->context));

		$currentgroup = $isseparategroups ? groups_get_course_group($this->page->course) : NULL;
		
		if ($this->page->course->id == SITEID or $this->page->context->contextlevel < CONTEXT_COURSE) {  // Site-level
			//Only show if is admin
			if(!checkIfUserIsAdmin()){
				return '';
			}
		}else{
			//Only show if is teacher or admin
			if(!checkIfUserIsTeacher($this->page->course->id) ){
				return '';
			}
		}                       
        //Get the user current group
        $users = getUserList($currentgroup,$this->page->course->id,$this->page->context->contextlevel,$this->page->context);

        //Now, we have in users, the list of users to show
        //Because they are online
        if (!empty($users)) {
            //Accessibility: Don't want 'Alt' text for the user picture; DO want it for the envelope/message link (existing lang string).
            //Accessibility: Converted <div> to <ul>, inherit existing classes & styles.
            $this->content->text .= "<ul class='list'>\n";
            if (isloggedin() && has_capability('moodle/site:sendmessage', $this->page->context)
                           && !empty($CFG->messaging) && !isguestuser()) {
                $canshowicon = true;
            } else {
                $canshowicon = false;
            }
            foreach ($users as $user) {
                $this->content->text .= '<li class="listentry">';

                if (isguestuser($user)) {
                    $this->content->text .= '<div class="user">'.$OUTPUT->user_picture($user, array('size'=>16, 'alttext'=>false));
                    $this->content->text .= get_string('guestuser').'</div>';

                } else {
                    $this->content->text .= '<div class="user">';
                    $this->content->text .= '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$this->page->course->id.'">';
                    $this->content->text .= $OUTPUT->user_picture($user, array('size'=>16, 'alttext'=>false, 'link'=>false)) .$user->fullname.'</a></div>';
                }
                if ($canshowicon and ($USER->id != $user->id) and !isguestuser($user)) {  // Only when logged in and messaging active etc
                    
                	$anchortagcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/delete') . '" alt="'. get_string("deletesession","block_uniquelogin_list") .'" />';
                    $anchortag = '<a href="'.$CFG->wwwroot.'/blocks/uniquelogin_list/processfile.php?id='.$user->id.'&courseid='.$this->page->course->id.'&sesskey=' . sesskey().'" title="'.get_string("deletesession","block_uniquelogin_list").'">'.$anchortagcontents .'</a>';
                    $this->content->text .= '<div class="message">'.$anchortag.'</div>';
                    
                    $anchortagcontents = '<img class="iconsmall" src="'.$OUTPUT->pix_url('t/message') . '" alt="'. get_string('messageselectadd') .'" />';
                    $anchortag = '<a href="'.$CFG->wwwroot.'/message/index.php?id='.$user->id.'" title="'.get_string('messageselectadd').'">'.$anchortagcontents .'</a>';

                    $this->content->text .= '<div class="message">'.$anchortag.'</div>';
                }
                $this->content->text .= "</li>\n";
            }
            $this->content->text .= '</ul><div class="clearer"><!-- --></div>';
        } else {
            $this->content->text .= "<div class=\"info\">".get_string("none")."</div>";
        }
        
        $this->content->text .= '<div class=\"info\"><a href="'.$CFG->wwwroot.'/blocks/uniquelogin_list/list_users.php?courseid='.$this->page->course->id.'">'.get_string("seeall","block_uniquelogin_list").'</a></div>';

        return $this->content;
    }
    
}


