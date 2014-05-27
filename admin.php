<?php

require_once '../../config.php';
require_once 'admin/lib.php';

require_login();

$admin_type = optional_param('type', 'person_mentor', PARAM_TEXT);

$context = context_system::instance();

$admin = (
    has_capability('block/student_gradeviewer:sportsadmin', $context) or
    has_capability('block/student_gradeviewer:academicadmin', $context)
);

if (!$admin) {
    print_error('no_permission', 'block_student_gradeviewer');
}

$classes = student_mentor_admin_page::gather_classes();

if (!isset($classes[$admin_type])) {
    $admin_type = 'person_mentor';
}

$form = $classes[$admin_type];

$base_url = new moodle_url('/blocks/student_gradeviewer/admin.php');

$_s = ues::gen_str('block_student_gradeviewer');
$blockname = $_s('pluginname');
$heading = $_s('admin');

$PAGE->set_context($context);
$PAGE->set_url($base_url);
$PAGE->set_title("$blockname: $heading");
$PAGE->set_heading("$blockname: $heading");
$PAGE->set_pagetype('mentor-administration');
$PAGE->navbar->add($SITE->shortname);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($heading);

echo $OUTPUT->header();

$to_name = function($class) { return $class->get_name(); };
echo $OUTPUT->single_select(
    $base_url, 'type',
    array_map($to_name, $classes), $admin_type
);

echo $OUTPUT->heading($form->get_name());

if ($data = data_submitted()) {
    $form->process_data($data);
}

echo $form->ui_filters();
echo $form->user_form();

echo $OUTPUT->footer();
