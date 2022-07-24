<?php
namespace Sovit;

if (!class_exists("\Sovit\Admin_Setting")) {
    class Admin_Setting {
        public $page = null;

        private $capability = "manage_options";

        private $menu_icon = "";

        private $menu_parent = false;

        private $menu_position = null;

        private $menu_title;

        private $page_id = "sovit-settings";

        private $page_title = "Settings";

        private $sanitize_callbacks = array();

        private $setting_key = "sovit_setting";

        private $tabs = null;

        public function __construct($page_id = false, $setting_key = false, $page_title = false, $menu_title = false) {
            add_action('admin_menu', array($this, 'admin_menu'), 20);
            add_action('admin_init', array($this, 'register_settings_fields'));
            if (false !== $page_id) {
                $this->set_page_id($page_id);
            }
            if (false !== $page_title) {
                $this->set_page_title($page_title);
            }
            if (false !== $menu_title) {
                $this->set_menu_title($menu_title);
            }
            if (false !== $setting_key) {
                $this->set_setting_key($setting_key);
            }
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 999);

            return $this;
        }

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

        final public function add_fields($tab_id, $section_id, array $fields) {
            foreach ($fields as $field_id => $field_args) {
                $this->add_field($tab_id, $section_id, $field_id, $field_args);
            }
            return $this;
        }

        final public function add_section($tab_id, $section_id, array $section_args = array()) {
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
                $section_args['fields'] = array();
            }

            $this->tabs[$tab_id]['sections'][$section_id] = $section_args;
            return $this;
        }

        public function admin_enqueue_scripts($hook) {

            if ($hook == $this->page) {
                wp_register_script('pickr', $this->get_url('assets/pickr.min.js'), array('jquery'), null, true);
                wp_enqueue_script($this->page_id . '-setting-js', $this->get_url('assets/settings.min.js'), array('jquery', 'pickr'), null, true);
                wp_register_style('pickr-css', $this->get_url('assets/pickr.css'), array());
                wp_enqueue_style($this->page_id . '-setting-css', $this->get_url('assets/settings.min.css'), array('pickr-css'));

            }
        }

        public function admin_menu() {
            if (false === $this->menu_parent) {
                $this->page = add_menu_page(
                    $this->page_title,
                    $this->menu_title,
                    $this->capability,
                    $this->page_id,
                    array($this, 'display_settings_page'),
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
                    array($this, 'display_settings_page'),
                    $this->menu_position
                );
            }
        }

        public function display_settings_page() {

            $tabs = $this->get_tabs();
            echo "<style>.settings-wrap .tab-wrapper {display: none;}.settings-wrap .tab-wrapper.tab-active {display: block;}</style>";
            echo '<div class="wrap settings-wrap ' . $this->page_id . '-wrap">';
            echo '<h1>' . $this->page_title . '</h1>';
            do_action("wppress/settings/" . $this->page_id . "/tabs/before");
            echo '<div id="settings-tabs-wrapper" class="nav-tab-wrapper">';

            foreach ($tabs as $tab_id => $tab) {
                if (empty($tab['sections']) && !isset($tab['render_callback'])) {
                    continue;
                }

                $active_class = '';

                if ('general' === $tab_id) {
                    $active_class = ' nav-tab-active';
                }

                echo "<a id='tab-nav-" . esc_attr($tab_id) . "' class='nav-tab{$active_class}' href='#tab-" . esc_attr($tab_id) . "'>{$tab['label']}</a>";
            }
            echo '</div>';
            echo '<form id="' . esc_attr($this->page_id) . '-settings-form" method="post" action="options.php">';

            settings_fields($this->page_id);

            foreach ($tabs as $tab_id => $tab) {
                if (empty($tab['sections']) && !isset($tab['render_callback'])) {
                    continue;
                }

                $active_class = '';

                if ('general' === $tab_id) {
                    $active_class = ' tab-active';
                }

                echo "<div id='tab-{$tab_id}' class='tab-wrapper" . esc_attr($active_class) . "'>";
                if (!empty($tab['render_callback']) && \is_callable($tab['render_callback'])) {
                    $tab['render_callback']();
                } else {
                    $first_section = true;
                    if (!empty($tab['sections'])) {
                        foreach ($tab['sections'] as $section_id => $section) {
                            $full_section_id = $this->page_id . '_' . $section_id . '_section';
                            if (false === $first_section) {
                                echo '<hr>';
                            }
                            $first_section = false;
                            if (!empty($section['label'])) {
                                echo "<h2>" . esc_html__($section['label']) . "</h2>";
                            }

                            if (!empty($section['callback']) && \is_callable($section['callback'])) {
                                $section['callback']();
                            } else {

                                echo '<table class="form-table">';

                                do_settings_fields($this->page_id, $full_section_id);

                                echo '</table>';
                            }
                        }
                    }
                    submit_button();
                }
                echo '</div>';
            }

            echo '</form>';
            echo '</div>';
            do_action("wppress/settings/" . $this->page_id . "/tabs/after");

        }

        public function get_page() {
            return $this->page;
        }

        public function get_url($file = "") {
            return trailingslashit(plugin_dir_url(__FILE__)) . $file;
        }

        public function on_options_update($new, $old, $opt) {
            $new_val = array();
            foreach ($new as $key => $val) {
                if (isset($this->sanitize_callbacks[$key]) and \is_callable($this->sanitize_callbacks[$key])) {
                    $new_val[$key] = $this->sanitize_callbacks[$key]($val);
                } else {
                    $new_val[$key] = $val;
                }
            }
            return $new_val;
        }

        public function register_setting_field($field_id, $field, $section_id, $setting_key = false, $value = array()) {
            $controls_class_name = '\Sovit\Controls';
            if (false === $setting_key) {
                $setting_key = $this->setting_key;
            }
            $args                = $field['field_args'];
            $args['name']        = $setting_key . '[' . $field_id . ']';
            $args['std']         = isset($args['std']) ? $args['std'] : "";
            $args['value']       = isset($value[$field_id]) ? $value[$field_id] : $args['std'];
            $args['id']          = sanitize_title($args['name']);
            $field['field_args'] = $args;

            $field_classes = array();

            if (!empty($field['class'])) {
                $field_classes[] = $field['field_args']['class'];
            }

            $field['field_args']['class'] = implode(' ', $field_classes);
            $render_callback              = array($controls_class_name, 'render');
            if (!empty($field['render_callback'])) {
                $render_callback = $field['render_callback'];
            }

            add_settings_field(
                $field['field_args']['id'],
                isset($field['label']) ? $field['label'] : '',
                $render_callback,
                $this->page_id,
                $section_id,
                $field['field_args']
            );
            if (isset($field["sanitize_callback"])) {
                $this->sanitize_callbacks[$field_id] = $field["sanitize_callback"];
            }
            if (isset($field["validate_callback"])) {
                $this->validate_callbacks[$field_id] = $field["validate_callback"];
            }
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

                        $this->register_setting_field($field_id, $field, $full_section_id, $this->setting_key, get_option($this->setting_key, array()));
                    }
                }
            }
            register_setting($this->page_id, $this->setting_key, array());
            add_filter("pre_update_option_" . $this->setting_key, array($this, "on_options_update"), 3, 10);
        }

        public function set_capability($capability) {
            $this->capability = $capability;
            return $this;
        }

        public function set_icon($icon) {
            $this->menu_icon = $icon;
            return $this;
        }

        public function set_menu_parent($parent) {
            $this->menu_parent = $parent;
            return $this;
        }

        public function set_menu_position($position) {
            $this->menu_position = $position;
            return $this;
        }

        public function set_menu_title($title) {
            $this->menu_title = $title;
            return $this;
        }

        public function set_page_id($page_id) {
            $this->page_id = $page_id;
            return $this;
        }

        public function set_page_title($title) {
            $this->page_title = $title;
            return $this;
        }

        public function set_setting_key($key) {
            $this->setting_key = $key;
            return $this;
        }

        protected function create_tabs() {
            $tabs = array(
                'general' => array(
                    'label'    => esc_html__('General'),
                    'sections' => array(),
                ),
            );
            return apply_filters('wppress/settings/' . $this->page_id . '/tabs', $tabs);
        }

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
    }
}
