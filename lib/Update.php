<?php
namespace Sovit;

if (!class_exists('\Sovit\Update')) {
    class Update
    {
        const SERVER = "https://wppress.net";
        private $support_required=false;

        public function __construct($file = false, $plugin_name = false, $itemid = false, $version = false, $license_key = false, $license_page = false)
        {
            $this->file         = plugin_basename($file);
            $this->plugin_name  = $plugin_name;
            $this->item_id      = $itemid;
            $this->version      = $version;
            $this->license_key  = $license_key;
            $this->license_page = $license_page;
            $this->product_page = "https://codecanyon.net/item/x/" . $this->item_id;
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'check_info'], 10, 3);
            add_action('admin_init', [$this, 'admin_init']);
            add_action('admin_footer', [$this, 'admin_footer']);
            add_action('wp_ajax_dismiss-wppress-rating-' . $this->item_id, [$this, 'dismiss_rating']);
            return $this;
        }

        public function admin_footer()
        {
            echo '<script type="text/javascript">!function(s){s(".dismiss-wppress-rating-' . $this->item_id . '").on("click",".notice-dismiss",function(i){s.get(ajaxurl,{action:"dismiss-wppress-rating-' . $this->item_id . '"})})}(jQuery);</script>';
        }

        public function admin_init()
        {
            add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 4);
            if ('' == $this->license_key) {
                add_filter('plugin_action_links_' . $this->file, [$this, 'plugin_action_link_activation']);
                add_action('admin_notices', [$this,
                    'license_nag',
                ]);
                add_action("after_plugin_row_" . $this->file, [$this, 'after_plugin_row'], 50, 2);
            }
            if (!get_user_meta(get_current_user_id(), "wpp_dismiss_" . $this->item_id, true)) {
                add_action('admin_notices', [$this,
                    'ask_rating',
                ]);
            }
            if(get_option( 'wppress_item_'.$this->item_id,false)){
                $this->get_update_data();
            }
            if($this->support_required==true && strtotime(get_option( 'wppress_item_'.$this->item_id, date('c',strtotime('-1 day')) ))<time()){
                add_action('admin_notices', [$this,
                    'support_expired',
                ]);
            }
        }

        public function after_plugin_row($file, $plugin_data)
        {

            $wp_list_table = _get_list_table('WP_Plugins_List_Table');

            printf(
                '<tr class="plugin-update-tr active" id="%s" data-slug="%s" data-plugin="%s">' .
                '<td colspan="%s" class="plugin-update colspanchange">' .
                '<div class="update-message notice inline %s notice-alt"><p>',
                esc_attr(dirname($this->file) . '-update-license-nag'),
                esc_attr(dirname($this->file)),
                esc_attr($this->file),
                esc_attr($wp_list_table->get_column_count()),
                "notice-warning"
            );
            echo "<a href=\"" . $this->license_page . "\">" . esc_html__("Enter valid license key/purchase code to enable automatic update.") . "</a>";
            echo "</p></td></tr>";

        }

        public function ask_rating()
        {
            Helper::add_notice(sprintf(esc_html__("Enjoying %s? Don't forget to rate us. Your rating is our strength & motivation.") . "\n\n" . esc_html__("It would only take a minute."), $this->plugin_name), "updated is-dismissible dismiss-wppress-rating-" . $this->item_id, [
                "url"   => $this->product_page,
                "label" => esc_html__("Give us 5 stars"),
            ]);
        }
        public function support_expired()
        {
            Helper::add_notice(sprintf(esc_html__("Support Expired: %s") . "\n\n" . esc_html__("You will still get lifetime updates for the plugin, but features which requires private API access will not work."), $this->plugin_name), "error", [
                "url"   => $this->product_page,
                "label" => esc_html__("Extend Support License"),
            ]);
        }

        public function check_info($def, $action, $arg)
        {
            if (!isset($arg->slug) || dirname($this->file) != $arg->slug) {
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

        public function check_update($transient)
        {
            if (empty($transient->checked)) {
                return $transient;
            }
            $info = $this->get_update_data();
            if (!$info) {
                return $transient;
            }
            if (is_object($info) && !empty($info)) {
                if (version_compare($info->version, $this->version, '<=')) {
                    return $transient;
                }
                $info->new_version                = $info->version;
                $info->package                    = $info->download_link;
                $transient->response[$this->file] = $info;
            }

            return $transient;
        }

        public function dismiss_rating()
        {
            update_user_meta(get_current_user_id(), "wpp_dismiss_" . $this->item_id, true);
            wp_send_json_success();
        }

        public function get_update_data()
        {
            $info                      = false;
            $query                     = [];
            $query['wpp-item-id']      = $this->item_id;
            $query['wpp-item-update']  = '' != $this->license_key ? $this->license_key : '';
            $query['wpp-item-version'] = $this->version;
            $query['wpp-site-url']     = \Sovit\Helper::maybeabsolute(site_url(), 'https://' . $_SERVER['HTTP_HOST']);
            $url                       = add_query_arg($query, self::SERVER);
            // Get the remote info
            $request = wp_remote_get($url);
            if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                $info = maybe_unserialize($request['body']);
                if (is_object($info)) {
                    $info->slug = dirname($this->file);
                    update_option( "wppress_item_".$this->item_id,$info->support_until);

                }
            }

            return $info;
        }

        public function license_nag()
        {
            Helper::add_notice(sprintf(esc_html__("Enter valid license key for %s"), $this->plugin_name), "error", [
                "url"   => $this->license_page,
                "label" => esc_html__("Enter License Key"),
            ]);

        }

        public function plugin_action_link_activation($links = [])
        {
            $links[] = '<a href="' . $this->license_page . '" style="font-weight:700; color:green;">' . esc_html__('Activate License') . '</a>';
            return $links;
        }

        public function plugin_row_meta($plugin_meta = [], $file=false, $plugin_data=false, $status=false)
        {
            if ($file == $this->file && false !== $this->product_page) {
                $plugin_meta[] = '<a href="' . $this->product_page . '" style="font-weight:700; color:green;" target="_blank">' . esc_html__('Give us ★★★★★ rating?') . '</a>';
            }
            return $plugin_meta;
        }

        public function set_file($file)
        {
            $this->file = plugin_basename($file);
            return $this;
        }

        public function set_item_id($id)
        {
            $this->item_id = $id;
            return $this;
        }
        public function support_is_required()
        {
            $this->support_required = true;
            return $this;
        }

        public function set_license($key)
        {
            $this->license_key = $key;
            return $this;
        }

        public function set_license_page($page)
        {
            $this->license_page = $page;
            return $this;
        }

        public function set_plugin_name($name)
        {
            $this->plugin_name = $name;
            return $this;
        }

        public function set_product_page($page)
        {
            $this->product_page = $page;
            return $this;
        }

        public function set_version($version)
        {
            $this->version = $version;
            return $this;
        }
    }
}
