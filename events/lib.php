<?php

require_once $CFG->dirroot . '/blocks/student_gradeviewer/classes/lib.php';
require_once $CFG->dirroot . '/blocks/ues_meta_viewer/classes/support.php';
require_once $CFG->dirroot . '/blocks/ues_meta_viewer/classes/lib.php';

class student_sports_gradeviewer implements supported_meta {
    public function wrapped_class() {
        return 'ues_user';
    }

    public function name() {
        return get_string('athletic', 'block_student_gradeviewer');
    }

    public function can_use() {
        $ctxt = get_context_instance(CONTEXT_SYSTEM);
        return has_capability('block/student_gradeviewer:sportsgrades', $ctxt);
    }

    public function defaults() {
        return array('username', 'idnumber', 'firstname', 'lastname');
    }
}

class sports_grade_dropdown extends meta_data_ui_element {
    public function __construct($name) {
        $this->meta = sports_mentor::meta();
        parent::__construct('specified_sport', $name);
    }

    public function format($user) {
        $sports = array();
        foreach ($this->meta as $name) {
            if (empty($user->$name)) {
                continue;
            }

            $sports[] = $user->$name;
        }

        return implode(', ', $sports);
    }

    public function sql($dsl) {
        $value = $this->value();

        if (empty($value)) {
            return $dsl;
        }

        $filters = ues::where()->value->equal($value)->name->in($this->meta);

        $sub_select =
            'SELECT userid FROM {' . ues_user::metatablename() . '}' .
            ' WHERE ' . $filters->sql();

        return $dsl->join("($sub_select)", 'sports')->on('id', 'userid');
    }

    public function html() {
        $sports = $this->gather_specified_sports();
        $select = html_writer::select(
            $sports, 'specified_sport', $this->value(), array()
        );

        return $select;
    }

    public function gather_specified_sports() {
        $sports = array('' => get_string('any'));

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('block/student_gradeviewer:sportsadmin', $context)) {
            $sports += sports_mentor::all_sports();
        } else {
            global $USER;

            $sports += sports_mentor::menu(ues::where('userid')->equal($USER->id));
        }

        return $sports;
    }
}

class sports_grade_meta_text extends meta_data_text_box {
    public function format($user) {
        switch ($this->key()) {
        case 'username':
            $base = 'blocks/student_gradeviewer/viewgrades/php';
            $url = new moodle_url($base, array('id' => $user->id));
            return html_writer::link($url, $user->username);
        case 'user_reg_status':
            return isset($user->user_reg_status) ?
                date('m-d-Y', $user->user_reg_status) :
                parent::format($user);
        default:
            return parent::format($user);
        }
    }
}

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

    public static function ues_meta_supported_types($data) {
        // Add links to the viewer
        $data->types['sports_grade'] = new student_sports_gradeviewer();

        // TODO: academic link
        return true;
    }

    public static function sports_grade_data_ui_keys($data) {
        // TODO: re-evaluate important fields... do they need FERPA?
        $keep = array(
            'username',
            'user_reg_status',
            'user_year',
            'user_college',
            'user_major',
            'user_keypadid'
        );

        $data->keys = array_filter($data->keys, function($key) use ($keep) {
            return in_array($key, $keep);
        });

        $data->keys[] = 'specified_sport';

        return true;
    }

    public static function sports_grade_data_ui_element($data) {
        $field = $data->ui_element->key();

        if ($field === 'specified_sport') {
            $name = get_string('specified_sport', 'block_student_gradeviewer');
            $data->ui_element = new sports_grade_dropdown($name);
        } else {
            $name = get_string($field, 'block_student_gradeviewer');
            $data->ui_element = new sports_grade_meta_text($field, $name);
        }

        return true;
    }

    public static function academic_grade_data_ui_keys($data) {
        return true;
    }

    public static function academic_grade_data_ui_element($data) {
        return true;
    }
}
