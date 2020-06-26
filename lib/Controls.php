<?php
namespace Sovit;

use Sovit\Helper;

if (!\defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists("\Sovit\Controls")) {

    class Controls
    {
        public static function render($field = [])
        {
            if (empty($field) || empty($field['id'])) {
                return;
            }

            $defaults = [
                'type'       => '',
                'attributes' => [],
                'std'        => '',
                'desc'       => '',
            ];

            $field = array_merge($defaults, $field);

            $method_name = $field['type'];

            if (!method_exists(__CLASS__, $method_name)) {
                $method_name = 'text';
            }
            self::$method_name($field);
            if (!empty($field['sub_desc'])) {
                echo Helper::kses($field['sub_desc']);
            }
            if (!empty($field['desc'])) {
                echo '<p class="description">';
                echo Helper::kses($field['desc']);
                echo '</p>';
            }
        }

        public function textarea($field = [])
        {
            if (empty($field['attributes']['class'])) {
                $field['attributes']['class'] = 'widefat';
            }
            if (empty($field['attributes']['rows'])) {
                $field['attributes']['rows'] = '5';
            }

            $attributes = Helper::render_html_attributes($field['attributes']);
            echo '<textarea id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" ' . $attributes . '/>' . esc_html($field['value']) . '</textarea>';

        }

        private static function checkbox(array $field)
        {
            echo '<label class="switch">';
            echo '<input type="checkbox" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="yes" ';
            checked($field['value'], 'yes');
            echo ' />';
            echo '<span></span>';
            echo '</label>';

        }

        private static function checkbox_list(array $field)
        {
            if (!\is_array($field['value'])) {
                $field['value'] = [];
            }
            foreach ($field['options'] as $option) {
                echo '<p><label class="switch">';
                echo '<input type="checkbox" name="' . esc_attr($field['name']) . '[]" value="' . esc_attr($option['value']) . '" ';
                checked(\in_array($option['value'], $field['value']));
                echo '/>';
                echo '<span></span>';

                echo '</label> ';
                echo esc_html__($option['name']);
                echo '</p>';
            }

        }

        private static function colorpicker(array $field)
        {
            $attributes = Helper::render_html_attributes($field['attributes']);
            echo '<input type="hidden" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '"/>';
            echo '<span class="colorpicker-pickr" data-value="' . esc_attr($field['value']) . '" data-target="' . esc_attr($field['id']) . '" data-default="' . esc_attr($field['std']) . '"></span>';
        }

        private static function editor($field = [])
        {
            wp_editor($field['value'], $field['id'], [
                'textarea_name' => $field['name'],
                'teeny'         => true,
                'media_buttons' => false,
            ]);
        }

        private static function raw_html(array $field)
        {
            if (empty($field['html'])) {
                // return;
            }
            echo '<div id="' . esc_attr($field['id']) . '">';

            echo '<div>' . Helper::kses($field['html']) . '</div>';

            echo '</div>';
        }

        private static function select(array $field)
        {
            $attributes = Helper::render_html_attributes($field['attributes']);

            echo '<select name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['id']) . '" ' . $attributes . '>';
            if (!empty($field['show_select'])) {
                echo '<option value="">— ' . esc_html__('Select') . ' —</option>';
            }

            foreach ($field['options'] as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ';
                selected($value, $field['value']);
                echo '>' . esc_html__($label) . '</option>';
            }
            echo '</select>';

        }

        private static function text(array $field)
        {
            if (empty($field['attributes']['class'])) {
                $field['attributes']['class'] = 'regular-text';
            }

            $attributes = Helper::render_html_attributes($field['attributes']);
            echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" value="' . esc_attr($field['value']) . '" ' . $attributes . '/>';

        }
    }
}
