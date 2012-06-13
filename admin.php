<?php

require_once '../../config.php';
require_once 'admin/lib.php';

require_login();

$admin_type = optional_param('type', 'person_mentor', PARAM_TEXT);

$context = get_context_instance(CONTEXT_SYSTEM);

$admin = (
    has_capability('block/student_gradeviewer:sportsadmin', $context) or
    has_capability('block/student_gradeviewer:academicadmin', $context)
);

if (!$admin) {
    print_error('no_permission', 'block_student_gradeviewer');
}

$classes = student_mentor_admin_page::gather_classes();

if (!isset($classes[$admin_type])) {
    $form = $classes[person_mentor::get_name()];
} else {
    $form = $classes[$admin_type];
}

$base_url = new moodle_url('/blocks/student_gradeviewer/admin.php', array(
    'type' => $admin_type
));

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
echo $OUTPUT->heading($form->get_name());

echo $form->ui_filters();
echo $form->user_form();

echo $OUTPUT->footer();
