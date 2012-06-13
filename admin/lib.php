<?php

require_once dirname(dirname(__FILE__)) . '/classes/lib.php';

abstract class student_mentor_admin_page {
    protected $name;
    protected $type;
    protected $path;

    // Capabilities required to use this admin page
    protected $capabilities = array();
    protected $errors = array();

    private $context;
    private $roleid;
    private $component = 'block_student_gradeviewer';

    public function __construct($type, $path, $capabilities = array()) {
        $this->type = $type;
        $this->name = get_string('admin_' . $type, 'block_student_gradeviewer');
        $this->path = $path;

        if (empty($capabilities)) {
            $capabilities = array(
                'block/student_gradeviewer:sportsadmin',
                'block/student_gradeviewer:academicadmin'
            );
        }

        $this->capabilities = $capabilities;
    }

    public function can_use() {
        $c = get_context_instance(CONTEXT_SYSTEM);

        return array_reduce($this->capabilities, function($in, $cap) use ($c) {
            return $in || has_capability($cap, $c);
        });
    }

    public function get_context() {
        if (empty($this->context)) {
            $this->context = get_context_instance(CONTEXT_SYSTEM);
        }

        return $this->context;
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
        if (empty($this->roleid)) {
            $this->roleid = get_config('block_student_gradeviewer', $this->get_type());
        }

        return $this->roleid;
    }

    public function perform_add($userid) {
        $params = array(
            'userid' => $userid,
            'path' => $this->path
        );

        $class = $this->type;

        if (class_exists($class)) {
            $assign = $class::get($params);
            if (empty($assign)) {
                $assign = $class::upgrade($params);
            }

            $assign->save();
        }
    }

    public function perform_remove($userid) {
        $params = array(
            'userid' => $userid,
            'path' => $this->path
        );

        $class = $this->type;
        if (class_exists($class) and $assign = $class::get($params)) {
            $class::delete($assign->id);
        }

    }

    abstract public function ui_filters();

    // TODO: justifiable custom output renderer?
    public function user_form() {
        global $OUTPUT;

        $table = new html_table();

        $selected_users = $this->get_selected_users();
        $selected_select = html_writer::select(
            $selected_users, 'selected_users', '', '',
            array('class' => 'main_selector', 'multiple' => 'multiple')
        );

        $available_users = $this->get_available_users();
        $available_select = html_writer::select(
            $available_users, 'available_users', '', '',
            array('class' => 'main_selector', 'multiple' => 'multiple')
        );

        $table->data = array(
            new html_table_row(array(
                $selected_select,
                $available_select
            ))
        );

        $hidden_input = function($name, $value) {
            return html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'name' => $name,
                'value' => $value
            ));
        };

        $hiddens = html_writer::tag('div',
            $hidden_input('path', $this->path) .
            $hidden_input('type', $this->get_type()) .
            $hidden_input('sesskey', sesskey())
        );

        $form = html_writer::tag('form', $hiddens . html_writer::table($table));

        return $OUTPUT->box($form);
    }

    // TODO: exclude already selected users?
    public function get_available_users() {
        global $DB;

        $search = optional_param('searchtext', '', PARAM_RAW);

        // Only show users after query
        if (empty($search)) {
            return array();
        }

        $fullname = $DB->sql_fullname();

        $fullname_like = $DB->sql_like($fullname, $search);
        $email_like = $DB->sql_like('email', $search);

        $sql = "SELECT * FROM {user}
            WHERE deleted != 0
              AND ($fullname_like OR $email_like)";

        $users = $DB->get_records_sql($sql, null, 0, 1000);

        $to_named = function($u) { return fullname($u); };
        return array_map($to_named, $users);
    }

    public function get_selected_users() {
        $class = $this->type;

        $selected = $class::get_all(ues::where()->path->equal($this->path));

        $to_named = function($assignment) {
            return fullname($assignment->user());
        };

        return array_map($to_named, $selected);
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

abstract class student_mentor_role_assign extends student_mentor_admin_page {
    public function perform_add($userid) {
        $context = $this->get_context();
        $roleid = $this->check_role();

        if (role_assign($roleid, $userid, $context->id, $this->component)) {
            parent::perform_add($userid);
        }
    }

    public function perform_remove($userid) {
        parent::perform_remove($userid);

        $context = $this->get_context();
        $roleid = $this->check_role();
        $component = $this->component;

        $params = array('userid' => $userid);
        $total_assigns = (
            $class::count($params) +
            person_mentor::count($params)
        );

        if (empty($total_assigns)) {
            role_unassign($roleid, $userid, $context->id, $component);
        }
    }
}
