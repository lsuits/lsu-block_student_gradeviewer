<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

abstract class student_gradeviewer_handlers {
    public static function user_deleted($user) {
        $params = array(
            'name' => 'user_mentor_person',
            'value' => $user->id
        );

        return ues_user::delete_meta($params);
    }
}
