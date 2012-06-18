<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

// TODO: fill in special querying abilities
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
