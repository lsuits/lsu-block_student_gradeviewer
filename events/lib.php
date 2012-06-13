<?php

require_once $CFG->dirroot . '/block/student_gradeviewer/classes/lib.php';

// TODO: perhaps handling the user role assignment events to clear DB
abstract class student_gradeviewer_handlers {
    public static function user_deleted($user) {
        $mentor = ues::where()->userid->equal($user->id);
        $student = ues::where()->path->equal($user->id);

        return (
            person_mentor::delete_all($student) and
            academic_mentor::delete_all($mentor) and
            sports_mentor::delete_all($mentor)
        );
    }
}
