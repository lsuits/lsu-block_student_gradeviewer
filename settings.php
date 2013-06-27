<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $roles = role_get_names(null,null,true);

    $internal_roles = array(
        'academic_mentor', 'sports_mentor',
        'academic_admin', 'sports_admin'
    );

    $settings->add(new admin_setting_heading('role_heading', '',
        get_string('role_help', 'block_student_gradeviewer')
    ));

    foreach ($internal_roles as $internal_role) {
        $settings->add(new admin_setting_configselect(
            'block_student_gradeviewer/' . $internal_role,
            get_string($internal_role, 'block_student_gradeviewer'),
            get_string($internal_role . '_help', 'block_student_gradeviewer'),
            key($roles), $roles
        ));
    }
}
