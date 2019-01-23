<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/lib.php');

ues::require_daos();

abstract class student_gradeviewer {
    public static function grade_gen($userid) {
        return function($course) use ($userid) {
            $name = $course->fullname;

            $course_item = grade_item::fetch_course_item($course->id);

            if (empty($course_item)) {
                return "$name -";
            }

            $grade = $course_item->get_grade($userid);
            if (empty($grade->id)) {
                $grade->finalgrade = null;
            }

            $display = grade_format_gradevalue($grade->finalgrade, $course_item);
            return "$name $display";
        };
    }

    public static function rank($context, $grade, $total_users) {
        $ids = array_keys($total_users);
        $count = count($ids);

        if (empty($grade->finalgrade)) {
            return "-/$count";
        }

        global $DB;

        $sql = 'SELECT COUNT(DISTINCT(g.userid))
            FROM {grade_grades} g
            WHERE g.itemid = :itemid
              AND g.finalgrade IS NOT NULL
              AND g.finalgrade > :final
              AND g.userid IN (' . implode(',', $ids) . ')';

        $params = array(
            'itemid' => $grade->grade_item->id,
            'final' => $grade->finalgrade
        );

        $rank = $DB->count_records_sql($sql, $params) + 1;

        return "$rank/$count";
    }
}

// Class to get grade report functions and variables from parent class in grade/report/lib.php

class grade_report_student_gradeviewer extends grade_report {

    /**
     * The user.
     * @var object $user
     */
    public $user;

    /**
     * The user's courses
     * @var array $courses
     */
    public $courses;

    /**
     * show course/category totals if they contain hidden items
     */
    var $showtotalsifcontainhidden;

    /**
     * An array of course ids that the user is a student in.
     * @var array $studentcourseids
     */
    public $studentcourseids;

    /**
     * An array of courses that the user is a teacher in.
     * @var array $teachercourses
     */
    public $teachercourses;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * Run for each course the user is enrolled in.
     * @param int $userid
     * @param string $context
     */
    public function __construct($userid, $courseid, $context) {
        global $CFG, $USER;
        parent::__construct($courseid, null, $context);

        // Get the user (for later use in grade/report/lib.php)
        $this->user = $USER;

        // Create an array (for later use in grade/report/lib.php)
        $this->showtotalsifcontainhidden = array();

        // Sanity check
        if ($courseid) {
            // Populate this (for later use in grade/report/lib.php)
            $this->showtotalsifcontainhidden[$courseid] = grade_get_setting($courseid, 'report_overview_showtotalsifcontainhidden', $CFG->grade_report_overview_showtotalsifcontainhidden);
        }
    }

    function process_action($target, $action) {
    }

    function process_data($data) {
        return $this->screen->process($data);
    }

    function get_blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade){

        return($this->blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade));
    }
}



// Returns the formatted course total item value give a userid and a course id
// If a course has no course grade item (no grades at all) the system returns '-'
// If a user has no course grade, the system returns '-'
// If a user has grades and the instructor allows those grades to be viewed, the system returns the final grade as stored in the database
// If a user has grades and the instructor has hidden the course grade item, the system returns the string 'hidden'
// If a user has grades and the instructor has hidden some of the users grades and those hidden items impact the course grade based on the instructor's settings, the system recalculates the course grade appropriately
function sg_get_grade_for_course($courseid, $userid, $course_total_item, $sggrade) {
    $course_context = context_course::instance($courseid);
    $canviewhidden = has_capability('moodle/grade:viewhidden', $course_context, $userid);
    $report = new grade_report_student_gradeviewer($userid, $courseid, null, $course_context);
    if (!$course_total_item) {
        $totalgrade = '-';
    }
    //$sggrade = $course_total_item->get_grade($userid, false);

echo'<br /><br />sggrade: ';
var_dump($sggrade);
echo' :sggrade<br /><br />';

    if (!$canviewhidden and $sggrade->finalgrade and is_numeric($sggrade->finalgrade)) {
        $adjustedgrade = $report->get_blank_hidden_total_and_adjust_bounds($courseid,
                                                                           $course_total_item,
                                                                           $sggrade);
	    echo'<br />shit: '; var_dump($adjustedgrade);
        // We temporarily adjust the view of this grade item - because the min and
        // max are affected by the hidden values in the aggregation.
        $course_total_item->grademax = $adjustedgrade['grademax'];
        $course_total_item->grademin = $adjustedgrade['grademin'];

    } else if (!is_null($sggrade)  || !is_numeric($sggrade->finalgrade)) {
        // Because the purpose of this block is to show MY grades as calculated for output
        // we make sure we adhere to how hiding grades impacts the total grade regardless
        // of if the user can view or not view hidden grades.
        // Example: User may be a site admin, faculty assistant, or some other
        // priveleged person and taking courses.
        // In any case, it's best to calculate grades how the instructor specifies.
        $adjustedgrade = $report->get_blank_hidden_total_and_adjust_bounds($courseid,
                                                                           $course_total_item,
                                                                           $sggrade->finalgrade);
        // We must use the specific max/min because it can be different for
        // each grade_grade when items are excluded from sum of grades.
        $course_total_item->grademin = $sggrade->get_grade_min();
        $course_total_item->grademax = $sggrade->get_grade_max();

    } else {
        $adjustedgrade = '';
    }

    if (isset($adjustedgrade['grade'])) {
        $totalgrade = grade_format_gradevalue($adjustedgrade['grade'], $course_total_item, true);
    } else {
        $totalgrade = '-';
    }
    if ($course_total_item->hidden) {
        $totalgrade = get_string('hidden', 'block_grades_at_a_glance');
    }
    return $totalgrade;
}

function sg_get_shortname($shortname) {
    $split = preg_split('/\s+for\s+/', $shortname);

    return $split[0];
}

