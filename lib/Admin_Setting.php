<?php
namespace Sovit;
if (!class_exists("\Sovit\Admin_Setting")) {
    class Admin_Setting {
        /**
         * @var mixed
         */
        public $page = null;

        /**
         * @var string
         */
        private $capability = "manage_options";

        /**
         * @var string
         */
        private $menu_icon = "";

        /**
         * @var mixed
         */
        private $menu_position = null;

        /**
         * @var mixed
         */
        private $menu_title;

        /**
         * @var mixed
         */
        private $menu_parent = false;

        /**
         * @var mixed
         */
        private $page_id = "sovit-settings";

        /**
         * @var mixed
         */
        private $page_title = "Settings";

        /**
         * @var string
         */
        private $setting_key = "sovit_setting";

        /**
         * @var mixed
         */
        private $tabs = null;

        /**
         * @param $page_id
         * @return mixed
         */
        public function __construct($page_id = false, $setting_key = false, $page_title = false, $menu_title = false) {
            add_action('admin_menu', [$this, 'admin_menu'], 20);
            add_action('admin_init', [$this, 'register_settings_fields']);
            if ($page_id !== false) {
                $this->set_page_id($page_id);
            }
            if ($page_title !== false) {
                $this->set_page_title($page_title);
            }
            if ($menu_title !== false) {
                $this->set_menu_title($menu_title);
            }
            if ($setting_key !== false) {
                $this->set_setting_key($setting_key);
            }
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);

            return $this;
        }

        /**
         * @param $tab_id
         * @param $section_id
         * @param $field_id
         * @param array $field_args
         * @return mixed
         */
        public function add_field($tab_id, $section_id, $field_id, array $field_args) {
            $this->ensure_tabs();

            if (!isset($this->tabs[$tab_id])) {
                // If the requested tab doesn't exists, use the first tab
                $tab_id = key($this->tabs);
            }

            if (!isset($this->tabs[$tab_id]['sections'][$section_id])) {
                // If the requested section doesn't exists, use the first section
                $section_id = key($this->tabs[$tab_id]['sections']);
            }

            if (isset($this->tabs[$tab_id]['sections'][$section_id]['fields'][$field_id])) {
                // Don't override an existing field
                return;
            }

            $this->tabs[$tab_id]['sections'][$section_id]['fields'][$field_id] = $field_args;
            return $this;
        }

        /**
         * @param $tab_id
         * @param $section_id
         * @param array $fields
         */
        final public function add_fields($tab_id, $section_id, array $fields) {
            foreach ($fields as $field_id => $field_args) {
                $this->add_field($tab_id, $section_id, $field_id, $field_args);
            }
            return $this;
        }

        /**
         * @param $tab_id
         * @param $section_id
         * @param array $section_args
         * @return null
         */
        final public function add_section($tab_id, $section_id, array $section_args = []) {
            $this->ensure_tabs();

            if (!isset($this->tabs[$tab_id])) {
                // If the requested tab doesn't exists, use the first tab
                $tab_id = key($this->tabs);
            }

            if (isset($this->tabs[$tab_id]['sections'][$section_id])) {
                // Don't override an existing section
                return;
            }

            if (!isset($section_args['fields'])) {
                $section_args['fields'] = [];
            }

            $this->tabs[$tab_id]['sections'][$section_id] = $section_args;
            return $this;
        }

        /**
         * @param $hook
         */
        public function admin_enqueue_scripts($hook) {

            if ($hook == $this->page) {
                wp_register_script('pickr', \Sovit\Helper::get_file_url(dirname(__FILE__) . '/assets/pickr.min.js'), ['jquery'], null, true);
                wp_enqueue_script($this->page_id . '-setting-js', \Sovit\Helper::get_file_url(dirname(__FILE__) . '/assets/settings.min.js'), ['jquery', 'pickr'], null, true);
                wp_register_style('pickr-css', \Sovit\Helper::get_file_url(dirname(__FILE__) . "/assets/pickr.css"), []);
                wp_enqueue_style($this->page_id . '-setting-css', \Sovit\Helper::get_file_url(dirname(__FILE__) . "/assets/settings.min.css"), ['pickr-css']);

            }
        }

        public function admin_menu() {
            if ($this->menu_parent === false) {
                $this->page = add_menu_page(
                    $this->page_title,
                    $this->menu_title,
                    $this->capability,
                    $this->page_id,
                    [$this, 'display_settings_page'],
                    $this->menu_icon,
                    $this->menu_position
                );
            } else {
                $this->page = add_submenu_page(
                    $this->menu_parent,
                    $this->page_title,
                    $this->menu_title,
                    $this->capability,
                    $this->page_id,
                    [$this, 'display_settings_page'],
                    $this->menu_position
                );
            }
        }

        public function display_settings_page() {

            $tabs = $this->get_tabs();
            echo "<style>.settings-wrap .tab-wrapper {display: none;}.settings-wrap .tab-wrapper.tab-active {display: block;}</style>";
            echo '<div class="wrap settings-wrap ' . $this->page_id . '-wrap">';
            echo '<h1>' . $this->page_title . '</h1>';
            echo '<div id="settings-tabs-wrapper" class="nav-tab-wrapper">';

            foreach ($tabs as $tab_id => $tab) {
                if (empty($tab['sections']) && !isset($tab['render_callback'])) {
                    continue;
                }

                $active_class = '';

                if ('general' === $tab_id) {
                    $active_class = ' nav-tab-active';
                }

                echo "<a id='tab-nav-{$tab_id}' class='nav-tab{$active_class}' href='#tab-{$tab_id}'>{$tab['label']}</a>";
            }
            echo '</div>';
            echo '<form id="' . $this->page_id . '-settings-form" method="post" action="options.php">';

            settings_fields($this->page_id);

            foreach ($tabs as $tab_id => $tab) {
                if (empty($tab['sections']) && !isset($tab['render_callback'])) {
                    continue;
                }

                $active_class = '';

                if ('general' === $tab_id) {
                    $active_class = ' tab-active';
                }

                echo "<div id='tab-{$tab_id}' class='tab-wrapper{$active_class}'>";
                if (!empty($tab['render_callback']) && \is_callable($tab['render_callback'])) {
                    $tab['render_callback']();
                }
                $first_section = true;
                if (!empty($tab['sections'])) {
                    foreach ($tab['sections'] as $section_id => $section) {
                        $full_section_id = $this->page_id . '_' . $section_id . '_section';
                        if (false === $first_section) {
                            echo '<hr>';
                        }
                        $first_section = false;
                        if (!empty($section['label'])) {
                            echo "<h2>{$section['label']}</h2>";
                        }

                        if (!empty($section['callback'])) {
                            $section['callback']();
                        }

                        echo '<table class="form-table">';

                        do_settings_fields($this->page_id, $full_section_id);

                        echo '</table>';
                    }
                }
                echo '</div>';
            }

            submit_button();
            echo '</form>';
            echo '</div>';
        }



        /**
         * @return mixed
         */
        public function get_page() {
            return $this->page;
        }

        /**
         * @param $field_id
         * @param $field
         * @param $section_id
         * @param $prefix
         * @param false $value
         * @return null
         */
        public function register_setting_field($field_id, $field, $section_id, $setting_key = false, $value = []) {
            $controls_class_name = '\Sovit\Controls';
            if (false === $setting_key) {
                $setting_key = $this->setting_key;
            }
            $args=$field['field_args'];
            $args['name']        = $setting_key . '[' . $field_id . ']';
            $args['std']        = isset($args['std'])?$args['std']:"";
            $args['value']        = isset($args['value'])?$args['value']:$args['std'];
            $args['id']        = sanitize_title($args['name']);
            $field['field_args']=$args;
           
           /* $field['field_args']['id'] = $field_id;
            $field['field_args']['std'] = isset($);
            $field['field_args']['value'] = $field_id;
            $field['field_args']       = apply_filters('wppress/settings/field_args', $field_id, $field['field_args'], $value);*/

            $field_classes = [];

            if (!empty($field['class'])) {
                $field_classes[] = $field['field_args']['class'];
            }

            $field['field_args']['class'] = implode(' ', $field_classes);
            $render_callback              = [$controls_class_name, 'render'];
            if (!empty($field['render_callback'])) {
                $render_callback = $field['render_callback'];
            }

            add_settings_field(
                $field['field_args']['id'],
                $field['label'] ?? '',
                $render_callback,
                $this->page_id,
                $section_id,
                $field['field_args']
            );
        }

        public function register_settings_fields() {
            $tabs = $this->get_tabs();

            foreach ($tabs as $tab_id => $tab) {
                if (!isset($tab['sections'])) {
                    continue;
                }

                foreach ($tab['sections'] as $section_id => $section) {
                    $full_section_id = $this->page_id . '_' . $section_id . '_section';

                    $label = isset($section['label']) ? $section['label'] : '';

                    $section_callback = isset($section['callback']) ? $section['callbak'] : '__return_empty_string';

                    add_settings_section($full_section_id, $label, $section_callback, $this->page_id);

                    foreach ($section['fields'] as $field_id => $field) {

                        $this->register_setting_field($field_id, $field, $full_section_id, $this->setting_key, get_option($this->setting_key,[]));
                    }
                }
            }
            register_setting($this->page_id, $this->setting_key, []);
        }

        /**
         * @param $capability
         * @return mixed
         */
        public function set_capability($capability) {
            $this->capability = $capability;
            return $this;
        }

        /**
         * @param $icon
         * @return mixed
         */
        public function set_icon($icon) {
            $this->menu_icon = $icon;
            return $this;
        }

        /**
         * @param $parent
         * @return mixed
         */
        public function set_menu_parent($parent) {
            $this->menu_parent = $parent;
            return $this;
        }

        /**
         * @param $position
         * @return mixed
         */
        public function set_menu_position($position) {
            $this->menu_position = $position;
            return $this;
        }

        /**
         * @param $title
         * @return mixed
         */
        public function set_menu_title($title) {
            $this->menu_title = $title;
            return $this;
        }

        /**
         * @param $id
         * @return mixed
         */
        public function set_page_id($page_id) {
            $this->page_id = $page_id;
            return $this;
        }

        /**
         * @param $title
         * @return mixed
         */
        public function set_page_title($title) {
            $this->page_title = $title;
            return $this;
        }

        /**
         * @param $key
         * @return mixed
         */
        public function set_setting_key($key) {
            $this->setting_key = $key;
            return $this;
        }

        protected function create_tabs() {
            $tabs = [
                'general' => [
                    'label'    => esc_html__('General'),
                    'sections' => [],
                ],
            ];
            // filter to create tabs wppress/settings/{PAGE_ID}/tabs
            return apply_filters('wppress/settings/' . $this->page_id . '/tabs', $tabs);
        }

        /**
         * @return mixed
         */
        protected function get_tabs() {
            $this->ensure_tabs();

            return $this->tabs;
        }

        private function ensure_tabs() {
            if (null === $this->tabs) {
                $this->tabs = $this->create_tabs();
                // action hook wppress/{PAGE_ID}/after_create_settings to add more settings
                do_action('wppress/settings/' . $this->page_id . '/after_create_settings', $this);
            }
        }

        /**
         * @param $file
         */
        private function get_file_url($file = __FILE__) {
            $file_path = str_replace("\\", "/", str_replace(str_replace("/", "\\", WP_CONTENT_DIR), "", $file));
            if ($file_path) {
                return content_url($file_path);
            }

            return false;
        }
    }
}