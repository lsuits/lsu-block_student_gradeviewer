<?php

require_once '../../config.php';
require_once 'lib.php';
require_once 'admin/lib.php';

require_login();

$admin_type = optional_param('type', 'person', PARAM_TEXT);

$context = get_context_instance(CONTEXT_SYSTEM);

$admin = (
    has_capability('block/student_gradeviewer:sportsadmin', $context) or
    has_capability('block/student_gradeviewer:academicadmin', $context)
);

if (!$admin) {
    print_error('no_permission', 'block_student_gradeviewer');
}

$base_url = new moodle_url('/blocks/student_gradeviewer/admin.php');

$_s = ues::gen_str('block_student_gradeviewer');
$blockname = $_s('pluginname');
$heading = $_s('admin');

$PAGE->set_context($context);
$PAGE->set_url($base_url);
$PAGE->set_title("$blockname: $heading");
$PAGE->set_heading("$blockname: $heading");
$PAGE->navbar->add($SITE->shortname);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

// Delegate admin pages to children who understand how things work internally

echo $OUTPUT->footer();
