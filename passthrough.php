<?php
//pass through to eCampus bookstore

require_once('../../config.php');
require_once('lib.php');

//during development
//error_reporting(E_ALL);
//ini_set('display_errors','stdout');
//ini_set('display_startup_errors', TRUE);

$courseid = optional_param('courseid', 0, PARAM_INT);		// this is optional, the Moodle $course->id

require_login();
$username = $USER->username;

$PAGE->set_url('/blocks/ecampus_tbird/passthrough.php', array('courseid' => $courseid));
$PAGE->set_pagelayout('base');

//get the course and check that user has access
$idnumber = 0;	//external SA/SIS system course id, passed to eCampus (i.e. not the Moodle internal $course->id)
if($courseid <> 0) {
	$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
	require_login($course);

	if ($course->id == SITEID) {	//should not happen because block() applicable_formats.
		error('eCampus access only works in courses');
	}
	//courseid we need to pass in is really the external SA system $course->idnumber, NOT $course->id
	if(!empty($course->idnumber))
		$idnumber = $course->idnumber;
} else {
	//coming from the My Moodle page! (most likely)
	//we need to set context manually (above is set by require_login($course))
	$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
}

//get the eCampus pass-through temporary access code
$error;
$accesscode = get_eCampus_accesscode($username,&$error);

//and now render the page with the login form
if($accesscode) {
	$PAGE->set_title(get_string('ecampuslogin','block_ecampus_tbird'));
	echo $OUTPUT->header();
	echo '<p>' . get_string('redirectfollowsshortly', 'block_ecampus_tbird') . '</p>';
	echo render_eCampus_login($username,$accesscode,$idnumber);
	add_to_log($courseid, 'ecampus_tbird','login','blocks/ecampus_tbird/README.TXT','eCampus Login');
} else {
	// unrecoverable errors have occured
	$PAGE->set_title(get_string('errorpagetitle','block_ecampus_tbird'));
	echo $OUTPUT->header();
	echo '<p>' . get_string('erroroccured','block_ecampus_tbird') . '<p>';
	echo '<p>' . get_string('errorcontactadmin','block_ecampus_tbird') . '<p>';
	echo '<p><font color="red">' . $error . '</font></p>';
	add_to_log($courseid, 'ecampus_tbird','error','blocks/ecampus_tbird/README.TXT',substr($error,0,200));
}
echo $OUTPUT->footer();
exit;