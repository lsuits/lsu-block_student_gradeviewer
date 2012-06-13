<?php

abstract class student_mentor_admin_page {
    protected $name;
    protected $type;
    protected $path;

    // Capabilities required to use this admin page
    protected $capabilities = array();
    protected $errors = array();

    public function can_use() {
        $c = get_context_instance(CONTEXT_SYSTEM);

        return array_reduce($this->capabilities, function($in, $cap) use ($c) {
            return $in || has_capbility($cap, $c);
        });
    }

    public function get_name() {
        return $this->name;
    }

    public function get_type() {
        return $this->type;
    }

    public function get_errors() {
        return $this->errors;
    }

    public function check_role() {
        $roleid = get_config('block_student_gradeviewer', $this->get_type());

        return $roleid;
    }

    public function perform_add($userid) {
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->check_role();
        $component = 'block_student_gradeviewer';

        if (role_assign($roleid, $userid, $context->id, $component)) {
            $user = ues_user::get(array('id' => $userid), true);
            $user->{'user_' . $this->get_type()} = $this->path;

            $user->save();
        }
    }

    public function perform_remove($userid) {
        $context = get_context_instance(CONTEXT_SYSTEM);
        $roleid = $this->check_role();

        $user = ues_user::get(array('id' => $userid), true);
        $field = 'user_' . $this->get_type();

        if (isset($user->$field)) {
            $user->delete_meta(array('name' => $field, 'userid' => $userid));
        }
    }

    public static function gather_files() {
        $filter = function($file) { return preg_match('/^admin_/', $file); };

        return array_filter(scandir(dirname(__FILE__)), $filter);
    }

    public static function gather_all_classes() {
        $instantiate = function($file) {
            require_once dirname(__FILE__) . '/' . $file;

            list($class, $__) = explode('.', $file);

            return new $class();
        };

        return array_map($instantiate, self::gather_files());
    }

    public static function gather_classes() {
        $acceptable = function($c) { return $c->can_use(); };
        $to_type = function($c) { return $c->get_type(); };

        $all_classes = self::gather_all_classes();

        $usable = array_filter($all_classes, $acceptable);
        $types = array_map($to_type, $usable);

        return array_combine($types, $usable);
    }
}
