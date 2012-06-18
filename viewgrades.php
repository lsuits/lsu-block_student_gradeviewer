<?php

require_once '../../config.php';
require_once 'lib.php';
require_once $CFG->dirroot . '/grade/lib.php';

require_login();

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

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

$_i = function($key) { return get_string($key, 'grades'); };

$courses = enrol_get_users_courses($id);

if (empty($courses)) {
    echo $OUTPUT->notification($_s('no_courses', $student));
    echo $OUTPUT->footer();
    exit();
}

$by_name = function($course) { return $course->fullname; };
$options = array_map($by_name, $courses);
$courseid = empty($courseid) ? key($courses) : $courseid;

echo $OUTPUT->single_select($base_url, 'courseid', $options, $courseid, null);

$course = $courses[$courseid];

echo $OUTPUT->heading($course->fullname, 3);

$graded = explode(',', get_config('moodle', 'gradebookroles'));

grade_regrade_final_grades($course->id);

$table = new html_table();

$table->head = array(
    $_i('itemname'), $_i('category'), $_i('overridden'),
    $_i('excluded'), $_i('range'), $_i('rank'),
    $_i('feedback'), $_i('finalgrade')
);

$tree = new grade_tree($course->id, true, true, null, !$CFG->enableoutcomes);

$context = get_context_instance(CONTEXT_COURSE, $course->id);
$total_users = get_role_users($graded, $context);

foreach ($tree->get_items() as $item) {
    $line = array();

    $parent = $item->get_parent_category();

    // Load item, but don't create
    $grade = $item->get_grade($id, false);

    if (empty($grade->id)) {
        $grade->finalgrade = null;
        $grade->feedback = null;
        $grade->feedbackformat = FORMAT_MOODLE;
    }
    $grade->grade_item = $item;

    $decimals = $item->get_decimals();

    $line[] = $item->get_name();
    $line[] = $parent->get_name();
    $line[] = $grade->is_overridden() ? 'Y' : 'N';
    $line[] = $grade->is_excluded() ? 'Y' : 'N';
    $line[] = format_float($item->grademin, $decimals) . ' - ' .
        format_float($item->grademax, $decimals);
    $line[] = student_gradeviewer::rank($context, $grade, $total_users);
    $line[] = format_text($grade->feedback, $grade->feedbackformat);
    $line[] = grade_format_gradevalue($grade->finalgrade, $item);

    $table->data[] = $line;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
