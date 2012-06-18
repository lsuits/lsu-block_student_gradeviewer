<?php

require_once $CFG->dirroot . '/enrol/ues/publiclib.php';
ues::require_daos();

abstract class mentor_base extends ues_external {
    var $user;

    public $userid;
    public $path;

    public function user($meta = false) {
        if (empty($this->user)) {
            $this->user = ues_user::by_id($this->userid, $meta);
        }

        return $this->user;
    }

    public static function menu($filters = array()) {
        $class = get_called_class();

        $mentors = $class::get_all($filters);

        $rtn = array();
        foreach ($mentors as $mentor) {
            $rtn[$mentor->path] = $mentor->path;
        }

        return $rtn;
    }

    abstract public function derive_path();
}

class academic_mentor extends mentor_base {
    public $college;
    public $major;
    public $year;

    public function college() {
        return $this->college;
    }

    public function major() {
        return $this->major;
    }

    public function year() {
        return $this->year;
    }

    public function derive_path() {
        list($college, $major, $year) = explode('/', $this->path);

        foreach (array('college', 'major', 'year') as $field) {
            if ($$field == 'NA') {
                continue;
            }

            $this->$field = $$field;
        }

        return array($this->college(), $this->major(), $this->year());
    }
}

class person_mentor extends mentor_base {
    public $student;

    public function student() {
        return $this->derive_path();
    }

    public function derive_path() {
        if (empty($this->student)) {
            $this->student = ues_user::by_id($this->path);
        }

        return $this->student;
    }
}

class sports_mentor extends mentor_base {
    public function derive_path() {
        return $this->path;
    }

    public static function meta() {
        $meta_names = ues_user::get_meta_names();

        $only_sports = function($name) {
            return preg_match('/sport/', $name);
        };

        return array_filter($meta_names, $only_sports);
    }

    public static function all_sports() {
        global $DB;

        $flatten = function($in, $name) use ($DB) {
            $sql = 'SELECT id, value
                FROM {enrol_ues_usermeta} WHERE name=? GROUP BY value';

            $sports = $DB->get_records_sql_menu($sql, array('name' => $name));

            return array_merge($in, array_values($sports));
        };

        $rtn = array_reduce(self::meta(), $flatten, array());
        if (empty($rtn)) {
            return array();
        } else {
            return array_combine($rtn, $rtn);
        }
    }
}
