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
        $c = $this->get_context();

        return array_reduce($this->capabilities, function($in, $cap) use ($c) {
            return $in || has_capability($cap, $c);
        });
    }

    public function get_context() {
        if (empty($this->context)) {
            $this->context = context_system::instance();
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

    public function message_params($userid) {
        return array('userid' => $userid, 'path' => $this->path);
    }

    public function perform_add($userid) {
        $params = $this->message_params($userid);

        $class = $this->type;

        if (class_exists($class)) {
            $assign = $class::get($params);
            if (empty($assign)) {
                $assign = $class::upgrade((object) $params);
            }

            $assign->save();
        }
    }

    public function perform_remove($userid) {
        $params = $this->message_params($userid);

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
            $selected_users, 'selected_users[]', '', '',
            array('class' => 'main_selector', 'multiple' => '', 'size' => 15)
        );

        $search = optional_param('searchtext', '', PARAM_RAW);

        $available_users = $this->get_available_users(
            array_keys($selected_users), $search
        );
        $available_select = html_writer::select(
            $available_users, 'available_users[]', '', '',
            array('class' => 'main_selector', 'multiple' => '', 'size' => 15)
        );

        $_s = ues::gen_str('block_student_gradeviewer');
        $header = array('class' => 'select_header');

        $table->head = array($_s('selected'), $_s('available'));

        $table->data = array(
            new html_table_row(array(
                $selected_select,
                $available_select
            )),
            new html_table_row(array('',
                html_writer::start_tag('div', array('class' => 'searchbox')) .
                html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'name' => 'searchtext',
                    'class' => 'searchtext',
                    'value' => $search,
                    'placeholder' => get_string('search')
                )) .
                html_writer::empty_tag('input', array(
                    'type' => 'submit',
                    'name' => 'search',
                    'class' => 'searchbutton',
                    'value' => get_string('search')
                )) .
                html_writer::end_tag('div')
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

        $submits = html_writer::start_tag('div', array('class' => 'submitbuttons'));
        $submits .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'add',
            'value' => get_string('add')
        ));
        $submits .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'remove',
            'value' => get_string('remove')
        ));
        $submits .= html_writer::end_tag('div');

        $form = html_writer::start_tag('form', array('method' => 'POST'));
        $form .= $hiddens . html_writer::table($table) . $submits;
        $form .= html_writer::end_tag('form');

        return $OUTPUT->box($form);
    }

    public function process_data($data) {
        $confirmed = confirm_sesskey($data->sesskey);

        if ($confirmed) {
            if (isset($data->add) and $data->available_users) {
                foreach ($data->available_users as $userid) {
                    $this->perform_add($userid);
                }
            } else if (isset($data->remove) and $data->selected_users) {
                foreach ($data->selected_users as $userid) {
                    $this->perform_remove($userid);
                }
            }
        }
    }

    public function get_available_users($selectedids, $search) {
        global $DB;

        // Only show users after query
        if (empty($search)) {
            return array();
        }

        $fullname = $DB->sql_fullname();

        $fullname_like = $DB->sql_like($fullname, ':fullname', false, false);
        $email_like = $DB->sql_like('email', ':email', false, false);

        $sql = "SELECT * FROM {user}
            WHERE deleted = 0 AND ($fullname_like OR $email_like)";

        if (!empty($selectedids)) {
            $sql .= ' AND id NOT IN (' . implode(',', $selectedids) . ')';
        }

        $params = array('fullname' => "%$search%", 'email' => "%$search%");

        $users = $DB->get_records_sql($sql, $params, 0, 100);

        $to_named = function($u) { return fullname($u) . " ($u->email)"; };
        return array_map($to_named, $users);
    }

    public function get_selected_users() {
        $class = $this->type;

        $selected = $class::get_all(ues::where()->path->equal($this->path));

        $rtn = array();
        foreach ($selected as $assign) {
            $user = $assign->user();
            $rtn[$assign->userid] = fullname($user) . " ($user->email)";
        }

        return $rtn;
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
    private $component = 'block_student_gradeviewer';

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
        $class = $this->type;

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
