<?php

class admin_person_mentor extends student_mentor_admin_page {
    public function __construct() {
        parent::__construct(
            'person_mentor',
            optional_param('path', 0, PARAM_INT)
        );

        $this->parents = array();
        if (has_capability($this->capabilities[0], $this->get_context())) {
            $this->parents[] = 'sports_mentor';
        }

        if (has_capability($this->capabilities[1], $this->get_context())) {
            $this->parents[] = 'academic_mentor';
        }
    }

    public function ui_filters() {
        global $OUTPUT;

        $options = array();

        $to_named = function($user) { return fullname($user); };
        $to_userid = function($assign) { return $assign->userid; };

        foreach ($this->parents as $parent) {
            $label = get_string($parent, 'block_student_gradeviewer');

            $assigns = $parent::get_all();

            if (empty($assigns)) {
                continue;
            }

            $userids = array_values(array_map($to_userid, $assigns));

            $filters = ues::where()->id->in($userids);
            $users = ues_user::get_all($filters, 'firstname, lastname ASC');

            if (isset($users[$this->path])) {
                $selected = fullname($users[$this->path]);
            }

            $options[] = array($label => array_map($to_named, $users));
        }

        $url = new moodle_url('/blocks/student_gradeviewer/admin.php', array(
            'type' => $this->get_type()
        ));

        $html = $OUTPUT->single_select($url, 'path', $options, $this->path);

        if (!empty($this->path)) {
            $assignment = get_string(
                'assigning_students',
                'block_student_gradeviewer', $selected
            );

            $html .= $OUTPUT->heading($assignment, 3);
        }

        return $html;
    }

    public function message_params($userid) {
        return array('userid' => $this->path, 'path' => $userid);
    }

    public function get_selected_users() {
        $filters = ues::where()->userid->equal($this->path);

        $selected = person_mentor::get_all($filters);

        $rtn = array();
        foreach ($selected as $assign) {
            $user = $assign->derive_path();
            $rtn[$assign->path] = fullname($user) . " ($user->email)";
        }

        return $rtn;
    }

    public function user_form() {
        global $OUTPUT;

        if (empty($this->path)) {
            $no_one = get_string('na_person', 'block_student_gradeviewer');
            return $OUTPUT->box($OUTPUT->notification($no_one));
        } else {
            return parent::user_form();
        }
    }
}
