<?php

namespace Sovit;
if (!class_exists('\Sovit\Helper')) {
    class Helper {
        /**
         * @param $message
         * @param $type
         * @param $btn
         */
        public static function add_notice($message = "", $type = "success", $btn = false) {
            echo "<div class=\"notice $type\">";
            echo wpautop($message);
            if (!empty($btn)) {
                echo wpautop(sprintf('<a href="%s"" class="button-primary">%s</a>', $btn['url'], $btn['label']));
            }
            echo "</div>";

        }

        /**
         * @param $file
         */
        public static function get_file_url($file = __FILE__) {
            $file_path = str_replace(str_replace("\\", "/", WP_CONTENT_DIR), "", str_replace("\\", "/", $file));
            if ($file_path) {
                return content_url($file_path);
            }

            return false;
        }

        /**
         * @param $taxonomy
         * @param $key
         * @param $value
         * @param $hideEmpty
         */
        public static function get_terms($taxonomy = 'category', $key = "slug", $value = "name", $hideEmpty = true) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => $hideEmpty,
            ]);
            if (is_wp_error($terms)) {
                return [];
            }

            return wp_list_pluck($terms, $value, $key);
        }

        /**
         * @param $filename
         * @param $suggested
         * @return string
         */
        public static function goodname($filename = "", $suggested = "wppress") {
            $part          = explode(".", $filename);
            $ext           = $part[count($part) - 1];
            $friendly_name = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $suggested), '-'));
            return $friendly_name . "." . strtolower($ext);
        }

        /**
         * @param $raw
         * @return mixed
         */
        public static function kses($raw) {

            $allowed_tags = [
                'a'                             => [
                    'class' => [],
                    'href'  => [],
                    'rel'   => [],
                    'title' => [],
                ],
                'abbr'                          => [
                    'title' => [],
                ],
                'b'                             => [],
                'blockquote'                    => [
                    'cite' => [],
                ],
                'cite'                          => [
                    'title' => [],
                ],
                'code'                          => [],
                'del'                           => [
                    'datetime' => [],
                    'title'    => [],
                ],
                'dd'                            => [],
                'div'                           => [
                    'class' => [],
                    'title' => [],
                    'style' => [],
                ],
                'dl'                            => [],
                'dt'                            => [],
                'em'                            => [],
                'h1'                            => [
                    'class' => [],
                ],
                'h2'                            => [
                    'class' => [],
                ],
                'h3'                            => [
                    'class' => [],
                ],
                'h4'                            => [
                    'class' => [],
                ],
                'h5'                            => [
                    'class' => [],
                ],
                'h6'                            => [
                    'class' => [],
                ],
                'i'                             => [
                    'class' => [],
                ],
                'img'                           => [
                    'alt'    => [],
                    'class'  => [],
                    'height' => [],
                    'src'    => [],
                    'width'  => [],
                ],
                'li'                            => [
                    'class' => [],
                ],
                'ol'                            => [
                    'class' => [],
                ],
                'p'                             => [
                    'class' => [],
                ],
                'q'                             => [
                    'cite'  => [],
                    'title' => [],
                ],
                'span'                          => [
                    'class' => [],
                    'title' => [],
                    'style' => [],
                ],
                'iframe'                        => [
                    'width'       => [],
                    'height'      => [],
                    'scrolling'   => [],
                    'frameborder' => [],
                    'allow'       => [],
                    'src'         => [],
                ],
                'strike'                        => [],
                'br'                            => [],
                'strong'                        => [],
                'data-wow-duration'             => [],
                'data-wow-delay'                => [],
                'data-wallpaper-options'        => [],
                'data-stellar-background-ratio' => [],
                'ul'                            => [
                    'class' => [],
                ],
            ];
            if (function_exists('wp_kses')) {
                // WP is here
                return wp_kses($raw, $allowed_tags);
            } else {
                return $raw;
            }
        }

        /**
         * @param array $array
         */
        public static function map_for_checkbox_list($array = []) {
            return array_map(function ($key, $value) {
                return ["value" => $key, "name" => $value];
            }, array_keys($array), $array);
        }

        /**
         * @param $rel
         * @param $base
         * @return mixed
         */
        public static function maybeabsolute($rel, $base) {
            if (parse_url($rel, PHP_URL_SCHEME) != '') {
                return $rel;
            }
            if ($rel[0] == '#' || $rel[0] == '?') {
                return $base . $rel;
            }
            $base = trailingslashit($base);
            extract(parse_url($base));

            $path = preg_replace('#/[^/]*$#', '', $path);
            if ($rel[0] == '/') {
                $path = '';
            }
            $abs = "$host$path/$rel";
            $re  = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
            for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {}
            return $scheme . '://' . $abs;
        }

        /**
         * @param $format
         * @return mixed
         */
        public static function phpToMoment($format) {
            $replacements = [
                'd' => 'DD',
                'D' => 'ddd',
                'j' => 'D',
                'l' => 'dddd',
                'N' => 'E',
                'S' => 'o',
                'w' => 'e',
                'z' => 'DDD',
                'W' => 'W',
                'F' => 'MMMM',
                'm' => 'MM',
                'M' => 'MMM',
                'n' => 'M',
                't' => '', // no equivalent
                'L' => '', // no equivalent
                'o' => 'YYYY',
                'Y' => 'YYYY',
                'y' => 'YY',
                'a' => 'a',
                'A' => 'A',
                'B' => '', // no equivalent
                'g' => 'h',
                'G' => 'H',
                'h' => 'hh',
                'H' => 'HH',
                'i' => 'mm',
                's' => 'ss',
                'u' => 'SSS',
                'e' => 'zz', // deprecated since version 1.6.0 of moment.js
                'I' => '', // no equivalent
                'O' => '', // no equivalent
                'P' => '', // no equivalent
                'T' => '', // no equivalent
                'Z' => '', // no equivalent
                'c' => '', // no equivalent
                'r' => '', // no equivalent
                'U' => 'X',
            ];
            return strtr($format, $replacements);
        }

        /**
         * @param array $attributes
         */
        public static function render_html_attributes(array $attributes) {
            $rendered_attributes = [];

            foreach ($attributes as $attribute_key => $attribute_values) {
                if (\is_array($attribute_values)) {
                    $attribute_values = implode(' ', $attribute_values);
                }

                $rendered_attributes[] = sprintf('%1$s="%2$s"', $attribute_key, esc_attr($attribute_values));
            }

            return implode(' ', $rendered_attributes);
        }

        /**
         * @param $string
         */
        public static function slugify($string = "") {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $string), '-'));
        }

        public static function timezone() {
            return array_flip([
                "Visitor's Default"                           => 'USER_BROWSER',
                '(UTC-11:00) Midway Island'                   => 'Pacific/Midway',
                '(UTC-11:00) Samoa'                           => 'Pacific/Samoa',
                '(UTC-10:00) Hawaii'                          => 'Pacific/Honolulu',
                '(UTC-09:00) Alaska'                          => 'US/Alaska',
                '(UTC-08:00) Pacific Time (US &amp; Canada)'  => 'America/Los_Angeles',
                '(UTC-08:00) Tijuana'                         => 'America/Tijuana',
                '(UTC-07:00) Arizona'                         => 'US/Arizona',
                '(UTC-07:00) Chihuahua'                       => 'America/Chihuahua',
                '(UTC-07:00) La Paz'                          => 'America/Chihuahua',
                '(UTC-07:00) Mazatlan'                        => 'America/Mazatlan',
                '(UTC-07:00) Mountain Time (US &amp; Canada)' => 'US/Mountain',
                '(UTC-06:00) Central America'                 => 'America/Managua',
                '(UTC-06:00) Central Time (US &amp; Canada)'  => 'US/Central',
                '(UTC-06:00) Guadalajara'                     => 'America/Mexico_City',
                '(UTC-06:00) Mexico City'                     => 'America/Mexico_City',
                '(UTC-06:00) Monterrey'                       => 'America/Monterrey',
                '(UTC-06:00) Saskatchewan'                    => 'Canada/Saskatchewan',
                '(UTC-05:00) Bogota'                          => 'America/Bogota',
                '(UTC-05:00) Eastern Time (US &amp; Canada)'  => 'US/Eastern',
                '(UTC-05:00) Indiana (East)'                  => 'US/East-Indiana',
                '(UTC-05:00) Lima'                            => 'America/Lima',
                '(UTC-05:00) Quito'                           => 'America/Bogota',
                '(UTC-04:00) Atlantic Time (Canada)'          => 'Canada/Atlantic',
                '(UTC-04:30) Caracas'                         => 'America/Caracas',
                '(UTC-04:00) La Paz'                          => 'America/La_Paz',
                '(UTC-04:00) Santiago'                        => 'America/Santiago',
                '(UTC-03:30) Newfoundland'                    => 'Canada/Newfoundland',
                '(UTC-03:00) Brasilia'                        => 'America/Sao_Paulo',
                '(UTC-03:00) Buenos Aires'                    => 'America/Argentina/Buenos_Aires',
                '(UTC-03:00) Georgetown'                      => 'America/Argentina/Buenos_Aires',
                '(UTC-03:00) Greenland'                       => 'America/Godthab',
                '(UTC-02:00) Mid-Atlantic'                    => 'America/Noronha',
                '(UTC-01:00) Azores'                          => 'Atlantic/Azores',
                '(UTC-01:00) Cape Verde Is.'                  => 'Atlantic/Cape_Verde',
                '(UTC+00:00) Casablanca'                      => 'Africa/Casablanca',
                '(UTC+00:00) Edinburgh'                       => 'Europe/London',
                '(UTC+00:00) Greenwich Mean Time : Dublin'    => 'Etc/Greenwich',
                '(UTC+00:00) Lisbon'                          => 'Europe/Lisbon',
                '(UTC+00:00) London'                          => 'Europe/London',
                '(UTC+00:00) Monrovia'                        => 'Africa/Monrovia',
                '(UTC+00:00) UTC'                             => 'UTC',
                '(UTC+01:00) Amsterdam'                       => 'Europe/Amsterdam',
                '(UTC+01:00) Belgrade'                        => 'Europe/Belgrade',
                '(UTC+01:00) Berlin'                          => 'Europe/Berlin',
                '(UTC+01:00) Bern'                            => 'Europe/Berlin',
                '(UTC+01:00) Bratislava'                      => 'Europe/Bratislava',
                '(UTC+01:00) Brussels'                        => 'Europe/Brussels',
                '(UTC+01:00) Budapest'                        => 'Europe/Budapest',
                '(UTC+01:00) Copenhagen'                      => 'Europe/Copenhagen',
                '(UTC+01:00) Ljubljana'                       => 'Europe/Ljubljana',
                '(UTC+01:00) Madrid'                          => 'Europe/Madrid',
                '(UTC+01:00) Paris'                           => 'Europe/Paris',
                '(UTC+01:00) Prague'                          => 'Europe/Prague',
                '(UTC+01:00) Rome'                            => 'Europe/Rome',
                '(UTC+01:00) Sarajevo'                        => 'Europe/Sarajevo',
                '(UTC+01:00) Skopje'                          => 'Europe/Skopje',
                '(UTC+01:00) Stockholm'                       => 'Europe/Stockholm',
                '(UTC+01:00) Vienna'                          => 'Europe/Vienna',
                '(UTC+01:00) Warsaw'                          => 'Europe/Warsaw',
                '(UTC+01:00) West Central Africa'             => 'Africa/Lagos',
                '(UTC+01:00) Zagreb'                          => 'Europe/Zagreb',
                '(UTC+02:00) Athens'                          => 'Europe/Athens',
                '(UTC+02:00) Bucharest'                       => 'Europe/Bucharest',
                '(UTC+02:00) Cairo'                           => 'Africa/Cairo',
                '(UTC+02:00) Harare'                          => 'Africa/Harare',
                '(UTC+02:00) Helsinki'                        => 'Europe/Helsinki',
                '(UTC+02:00) Istanbul'                        => 'Europe/Istanbul',
                '(UTC+02:00) Jerusalem'                       => 'Asia/Jerusalem',
                '(UTC+02:00) Kyiv'                            => 'Europe/Helsinki',
                '(UTC+02:00) Pretoria'                        => 'Africa/Johannesburg',
                '(UTC+02:00) Riga'                            => 'Europe/Riga',
                '(UTC+02:00) Sofia'                           => 'Europe/Sofia',
                '(UTC+02:00) Tallinn'                         => 'Europe/Tallinn',
                '(UTC+02:00) Vilnius'                         => 'Europe/Vilnius',
                '(UTC+03:00) Baghdad'                         => 'Asia/Baghdad',
                '(UTC+03:00) Kuwait'                          => 'Asia/Kuwait',
                '(UTC+03:00) Minsk'                           => 'Europe/Minsk',
                '(UTC+03:00) Nairobi'                         => 'Africa/Nairobi',
                '(UTC+03:00) Riyadh'                          => 'Asia/Riyadh',
                '(UTC+03:00) Volgograd'                       => 'Europe/Volgograd',
                '(UTC+03:30) Tehran'                          => 'Asia/Tehran',
                '(UTC+04:00) Abu Dhabi'                       => 'Asia/Muscat',
                '(UTC+04:00) Baku'                            => 'Asia/Baku',
                '(UTC+04:00) Moscow'                          => 'Europe/Moscow',
                '(UTC+04:00) Muscat'                          => 'Asia/Muscat',
                '(UTC+04:00) St. Petersburg'                  => 'Europe/Moscow',
                '(UTC+04:00) Tbilisi'                         => 'Asia/Tbilisi',
                '(UTC+04:00) Yerevan'                         => 'Asia/Yerevan',
                '(UTC+04:30) Kabul'                           => 'Asia/Kabul',
                '(UTC+05:00) Islamabad'                       => 'Asia/Karachi',
                '(UTC+05:00) Karachi'                         => 'Asia/Karachi',
                '(UTC+05:00) Tashkent'                        => 'Asia/Tashkent',
                '(UTC+05:30) Chennai'                         => 'Asia/Calcutta',
                '(UTC+05:30) Kolkata'                         => 'Asia/Kolkata',
                '(UTC+05:30) Mumbai'                          => 'Asia/Calcutta',
                '(UTC+05:30) New Delhi'                       => 'Asia/Calcutta',
                '(UTC+05:30) Sri Jayawardenepura'             => 'Asia/Calcutta',
                '(UTC+05:45) Kathmandu'                       => 'Asia/Katmandu',
                '(UTC+06:00) Almaty'                          => 'Asia/Almaty',
                '(UTC+06:00) Astana'                          => 'Asia/Dhaka',
                '(UTC+06:00) Dhaka'                           => 'Asia/Dhaka',
                '(UTC+06:00) Ekaterinburg'                    => 'Asia/Yekaterinburg',
                '(UTC+06:30) Rangoon'                         => 'Asia/Rangoon',
                '(UTC+07:00) Bangkok'                         => 'Asia/Bangkok',
                '(UTC+07:00) Hanoi'                           => 'Asia/Bangkok',
                '(UTC+07:00) Jakarta'                         => 'Asia/Jakarta',
                '(UTC+07:00) Novosibirsk'                     => 'Asia/Novosibirsk',
                '(UTC+08:00) Beijing'                         => 'Asia/Hong_Kong',
                '(UTC+08:00) Chongqing'                       => 'Asia/Chongqing',
                '(UTC+08:00) Hong Kong'                       => 'Asia/Hong_Kong',
                '(UTC+08:00) Krasnoyarsk'                     => 'Asia/Krasnoyarsk',
                '(UTC+08:00) Kuala Lumpur'                    => 'Asia/Kuala_Lumpur',
                '(UTC+08:00) Perth'                           => 'Australia/Perth',
                '(UTC+08:00) Singapore'                       => 'Asia/Singapore',
                '(UTC+08:00) Taipei'                          => 'Asia/Taipei',
                '(UTC+08:00) Ulaan Bataar'                    => 'Asia/Ulan_Bator',
                '(UTC+08:00) Urumqi'                          => 'Asia/Urumqi',
                '(UTC+09:00) Irkutsk'                         => 'Asia/Irkutsk',
                '(UTC+09:00) Osaka'                           => 'Asia/Tokyo',
                '(UTC+09:00) Sapporo'                         => 'Asia/Tokyo',
                '(UTC+09:00) Seoul'                           => 'Asia/Seoul',
                '(UTC+09:00) Tokyo'                           => 'Asia/Tokyo',
                '(UTC+09:30) Adelaide'                        => 'Australia/Adelaide',
                '(UTC+09:30) Darwin'                          => 'Australia/Darwin',
                '(UTC+10:00) Brisbane'                        => 'Australia/Brisbane',
                '(UTC+10:00) Canberra'                        => 'Australia/Canberra',
                '(UTC+10:00) Guam'                            => 'Pacific/Guam',
                '(UTC+10:00) Hobart'                          => 'Australia/Hobart',
                '(UTC+10:00) Melbourne'                       => 'Australia/Melbourne',
                '(UTC+10:00) Port Moresby'                    => 'Pacific/Port_Moresby',
                '(UTC+10:00) Sydney'                          => 'Australia/Sydney',
                '(UTC+10:00) Yakutsk'                         => 'Asia/Yakutsk',
                '(UTC+11:00) Vladivostok'                     => 'Asia/Vladivostok',
                '(UTC+12:00) Auckland'                        => 'Pacific/Auckland',
                '(UTC+12:00) Fiji'                            => 'Pacific/Fiji',
                '(UTC+12:00) International Date Line West'    => 'Pacific/Kwajalein',
                '(UTC+12:00) Kamchatka'                       => 'Asia/Kamchatka',
                '(UTC+12:00) Magadan'                         => 'Asia/Magadan',
                '(UTC+12:00) Marshall Is.'                    => 'Pacific/Fiji',
                '(UTC+12:00) New Caledonia'                   => 'Asia/Magadan',
                '(UTC+12:00) Solomon Is.'                     => 'Asia/Magadan',
                '(UTC+12:00) Wellington'                      => 'Pacific/Auckland',
                '(UTC+13:00) Nuku\'alofa'                     => 'Pacific/Tongatapu',
            ]);

        }
    }
}
