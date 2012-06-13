<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

abstract class student_gradeviewer_handlers {
    public static function user_deleted($user) {
        // Unload deleted student
        $mentee_params = array(
            'name' => 'user_person_mentor',
            'value' => $user->id
        );

        // Unload delted mentor
        $mentor_params = array(
            'name' => 'user_person_mentor',
            'userid' => $user->id
        );

        return (
            ues_user::delete_meta($mentee_params) and
            ues_user::delete_meta($mentor_params)
        );
    }
}
