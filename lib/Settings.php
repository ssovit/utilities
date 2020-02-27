<?php
namespace Sovit;
if (!class_exists("\Sovit\Settings")) {

    class Settings {
        /**
         * @var string
         */
        private static $key = 'wppress';

        /**
         * @param $key
         *
         * @return mixed
         */
        public static function get_option($key,$default=false) {
            $options = self::get_options();

            return isset($options[$key]) ? $options[$key] : $default;
        }

        public static function get_options() {
            return get_option(self::$key, []);
        }

        /**
         * @param $key
         */
        public static function set_setting_key($key) {
            self::$key = $key;
        }

        /**
         * @param $key
         * @param $value
         */
        public static function update_option($key, $value) {
            $options       = self::get_options();
            $options[$key] = $value;
            update_option(self::$key, $options);
            return true;
        }
    }
}