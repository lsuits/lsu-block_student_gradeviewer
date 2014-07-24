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
        $ctxt = context_system::instance();
        return has_capability('block/student_gradeviewer:sportsgrades', $ctxt);
    }

    public function defaults() {
        return array('username', 'idnumber', 'firstname', 'lastname');
    }
}

class sports_grade_dropdown extends meta_data_ui_element {
    public function __construct($name) {
        $this->meta = sports_mentor::meta();
        $this->context = context_system::instance();
        $this->sports = $this->gather_specified_sports();
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

    public function sub($filters) {
        return 'SELECT userid FROM {' . ues_user::metatablename() . '} WHERE ' .
            $filters->sql();
    }

    public function sql($dsl) {
        $value = $this->value();

        // Tried to spoof it
        if (!isset($this->sports[$value])) {
            $value = null;
        }

        if (empty($value)) {
            if (has_capability('block/student_gradeviewer:sportsadmin', $this->context)) {
                return $dsl->user_sport1->not_equal('');
            } else {
                global $USER;

                $params = array('userid' => $USER->id);

                $sports = sports_mentor::menu($params);
                $people = person_mentor::menu($params);

                $sport_sub = $this->sub(ues::where()
                    ->value->in($sports)->name->in($this->meta));

                if (!empty($people)) {
                    $person_select = 'SELECT id AS userid FROM {user} WHERE ';
                    $person_select .= ues::where()->id->in($people)->sql();

                    $sport_sub = "($sport_sub) UNION ($person_select)";
                }

                return $dsl->join("($sport_sub)", 'sports')->on('id', 'userid');
            }
        }

        $filters = ues::where()->value->equal($value)->name->in($this->meta);
        $sub_select = $this->sub($filters);

        return $dsl->join("($sub_select)", 'sports')->on('id', 'userid');
    }

    public function html() {
        $select = html_writer::select(
            $this->sports, 'specified_sport',
            $this->value(), array()
        );

        return $select;
    }

    public function gather_specified_sports() {
        $sports = array('' => get_string('any'));

        if (has_capability('block/student_gradeviewer:sportsadmin', $this->context)) {
            $sports += sports_mentor::all_sports();
        } else {
            global $USER;

            $sports += sports_mentor::menu(array('userid' => $USER->id));
            unset($sports['NA']);
        }

        return $sports;
    }
}

class sports_grade_meta_text extends meta_data_text_box {
    public function format($user) {
        switch ($this->key()) {
        case 'username':
            $base = '/blocks/student_gradeviewer/viewgrades.php';
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
            'idnumber',
            'firstname',
            'lastname',
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
