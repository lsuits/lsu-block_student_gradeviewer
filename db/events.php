<?php

$events = array('user_deleted');

$map = function($event) {
    return array(
        'handlerfile' => '/bocks/student_gradeviewer/events/lib.php',
        'handlerfunction' => array('student_gradeviewer_handlers', $event),
        'schedule' => 'instant'
    );
};

$handlers = array_combine($events, array_map($map, $events));
