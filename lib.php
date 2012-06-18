<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

// TODO: fill in special querying abilities
abstract class student_gradeviewer {
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
              AND g.userid IN (' . implode(',', $ids) . ')';

        $params = array('itemid' => $grade->grade_item->id);

        $rank = $DB->count_records_sql($sql, $params);

        return "$rank/$count";
    }
}
