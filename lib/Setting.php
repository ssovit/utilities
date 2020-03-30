<?php

namespace Sovit;
if (!class_exists("\Sovit\Setting")) {

    class Setting {
        /**
         * @var string
         */
        private $key = 'wppress';

        /**
         * @param $key
         */
        public function __construct($key = "wppress") {
            $this->key = $key;
        }

        /**
         * @param $key
         * @param $default
         */
        public function get_option($key, $default = false) {
            $options = $this->get_options();
            return isset($options[$key]) ? $options[$key] : $default;
        }

        public function get_options() {
            return get_option($this->key, []);
        }

        /**
         * @param $key
         * @param $value
         */
        public function update_option($key, $value) {
            $options       = $this->get_options();
            $options[$key] = $value;
            update_option($this->key, $options);
            return true;
        }
    }
}