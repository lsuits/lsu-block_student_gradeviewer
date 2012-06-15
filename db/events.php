<?php

$events = array(
    // System events
    'user_deleted',
    // UES Meta Viewer events (for person queries)
    'ues_meta_supported_types',
    'sports_grade_data_ui_keys',
    'sports_grade_data_ui_element',
    'academic_grade_data_ui_keys',
    'academic_grade_data_ui_element'
);

$map = function($event) {
    return array(
        'handlerfile' => '/blocks/student_gradeviewer/events/lib.php',
        'handlerfunction' => array('student_gradeviewer_handlers', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($map, $events));
