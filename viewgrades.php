<?php

require_once '../../config.php';
require_once 'lib.php';

require_login();

// TODO: check this student belongs to this mentor
$id = required_param('id', PARAM_INT);

$user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);

$context = get_context_instance(CONTEXT_SYSTEM);

$mentor = (
    has_capability('block/student_gradeviewer:sportsgrades', $context) or
    has_capability('block/student_gradeviewer:viewgrades', $context)
);

$admin = (
    has_capability('block/student_gradeviewer:sportsadmin', $context) or
    has_capability('block/student_gradeviewer:academicadmin', $context)
);

if (!$mentor) {
    print_error('no_permission', 'block_student_gradeviewer');
}

$base_url = new moodle_url('/blocks/student_gradeviewer/viewgrades.php', array(
    'id' => $id
));

$_s = ues::gen_str('block_student_gradeviewer');

$blockname = $_s('pluginname');
$student = fullname($user);

$PAGE->set_context($context);
$PAGE->set_url($base_url);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($student);
$PAGE->set_title($_s('viewgrades', $student));
$PAGE->set_heading("$blockname: $student");

echo $OUTPUT->header();
echo $OUTPUT->heading($_s('viewgrades', $student));

$courses = enrol_get_users_courses($id);

// TODO: display grade table

echo $OUTPUT->footer();
