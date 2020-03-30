<?php

namespace Sovit;

if (!class_exists('\Sovit\Update')) {
    class Update {
        const SERVER = "https://wppress.net";

        /**
         * @param $file
         * @param $plugin_name
         * @param $itemid
         * @param $version
         * @param $license_key
         * @param $license_setting_page
         */
        public function __construct($file, $plugin_name, $itemid, $version, $license_key, $license_page) {
            $this->file         = $file;
            $this->plugin_name  = $plugin_name;
            $this->item_id      = $itemid;
            $this->version      = $version;
            $this->license_key  = $license_key;
            $this->license_page = $license_page;
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'check_info'], 10, 3);
            add_action('admin_init', [$this, 'admin_init']);

            return $this;
        }

        public function admin_init() {
            if ('' == $this->license_key) {
                add_filter('plugin_action_links_' . plugin_basename($this->file), [$this, 'plugin_action_link']);
                add_action('admin_notices', [$this,
                    'license_nag',
                ]);
                add_action("after_plugin_row_" . plugin_basename($this->file), [$this, 'after_plugin_row'], 50, 2);
            }
        }

        /**
         * @param $file
         * @param $plugin_data
         */
        public function after_plugin_row($file, $plugin_data) {

            $wp_list_table = _get_list_table('WP_Plugins_List_Table');

            printf(
                '<tr class="plugin-update-tr active" id="%s" data-slug="%s" data-plugin="%s">' .
                '<td colspan="%s" class="plugin-update colspanchange">' .
                '<div class="update-message notice inline %s notice-alt"><p>',
                esc_attr(dirname(plugin_basename($this->file)) . '-update-license-nag'),
                esc_attr(dirname(plugin_basename($this->file))),
                esc_attr($file),
                esc_attr($wp_list_table->get_column_count()),
                "notice-warning"
            );
            echo "<a href=\"" . $this->license_page . "\">" . esc_html__("Enter valid license key/purchase code to enable automatic update.","wppress-covid19") . "</a>";
            echo "</p></td></tr>";

        }

        /**
         * @param $def
         * @param $action
         * @param $arg
         * @return mixed
         */
        public function check_info($def, $action, $arg) {
            if (!isset($arg->slug) || $arg->slug != dirname(plugin_basename($this->file))) {
                return false;
            }
            if ('' == $this->license_key) {
                return new \WP_Error('plugins_api_failed', 'License key missing or invalid.</p> <p><a href="' . $this->license_page . '">Enter valid license key and try again.</a>', $request->get_error_message());
            }
            $info = $this->get_update_data();
            if (\is_object($info) && !empty($info)) {
                $def = $info;
            }

            return $def;
        }

        /**
         * @param $transient
         * @return mixed
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }
            $info = $this->get_update_data();
            if (!$info) {
                return $transient;
            }
            if (\is_object($info) && !empty($info)) {
                if (version_compare($info->version, $this->version, '<=')) {
                    return $transient;
                }
                $info->new_version                                 = $info->version;
                $info->package                                     = $info->download_link;
                $transient->response[plugin_basename($this->file)] = $info;
            }

            return $transient;
        }

        /**
         * @return mixed
         */
        public function get_update_data() {
            $info                      = false;
            $query                     = [];
            $query['wpp-item-id']      = $this->item_id;
            $query['wpp-item-update']  = '' != $this->license_key ? $this->license_key : '';
            $query['wpp-item-version'] = $this->version;
            $query['wpp-site-url']     = \Sovit\Helper::maybeabsolute(site_url(), 'https://' . $_SERVER['HTTP_HOST']);
            $url                       = add_query_arg($query, self::SERVER);

            // Get the remote info
            $request = wp_remote_get($url);
            if (!is_wp_error($request) || 200 === wp_remote_retrieve_response_code($request)) {
                $info = maybe_unserialize($request['body']);
                if (\is_object($info)) {
                    $info->slug = dirname(plugin_basename($this->file));
                }
            }

            return $info;
        }

        public function license_nag() {
            \Sovit\Helper::add_notice(sprintf(esc_html__("Enter valid license key for %s"), $this->plugin_name), "error", [
                "url"   => $this->license_page,
                "label" => esc_html__("Enter License Key"),
            ]);

        }

        /**
         * @param array $links
         * @return mixed
         */
        public function plugin_action_link($links = []) {
            $links[] = '<a href="' . $this->license_page . '" style="font-weight:700; color:green;">' . esc_html__('Activate License') . '</a>';

            return $links;
        }

        /**
         * @param $id
         * @return mixed
         */
        public function set_item_id($id) {
            $this->item_id = $id;
            return $this;
        }

        /**
         * @param $key
         * @return mixed
         */
        public function set_license($key) {
            $this->license_key = $key;
            return $this;
        }

        /**
         * @param $page
         * @return mixed
         */
        public function set_license_page($page) {
            $this->license_page = $page;
            return $this;
        }

        /**
         * @param $file
         */
        public function set_file($file) {
            $this->file = $file;
        }

        /**
         * @param $name
         * @return mixed
         */
        public function set_plugin_name($name) {
            $this->plugin_name = $name;
            return $this;
        }

        /**
         * @param $version
         * @return mixed
         */
        public function set_version($version) {
            $this->version = $version;
            return $this;
        }
    }
}
