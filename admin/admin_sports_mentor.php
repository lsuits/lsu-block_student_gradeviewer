<?php

class admin_sports_mentor extends student_mentor_role_assign {
    public function __construct() {
        parent::__construct(
            'sports_mentor',
            optional_param('path', 'NA', PARAM_TEXT),
            array('block/student_gradeviewer:sportsadmin')
        );
    }

    public function ui_filters() {
        global $OUTPUT;

        $no_sports = get_string('na_sports', 'block_student_gradeviewer');
        $sports = array('NA' => $no_sports) + sports_mentor::all_sports();

        $url = new moodle_url('/blocks/student_gradeviewer/admin.php', array(
            'type' => $this->get_type()
        ));

        $assigning_to = get_string(
            'assigning_to', 'block_student_gradeviewer', $sports[$this->path]
        );

        return $OUTPUT->single_select($url, 'path', $sports, $this->path) .
            $OUTPUT->heading($assigning_to, 3);
    }
}
