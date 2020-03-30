<?php

namespace Sovit;
if (!class_exists('\Sovit\Routes')) {
    class Routes {
        /**
         * @var array
         */
        protected $routes = [];

        /**
         * @var string
         */
        private $restNamespace = '';

        /**
         * @param $restNamespace
         */
        public function __construct($restNamespace) {
            $this->restNamespace = $restNamespace;
            add_action('rest_api_init', [$this, '_restInit']);
        }

        public function _restInit() {
            foreach ($this->routes as $route) {
                register_rest_route($this->restNamespace, $route->getRestEndpoint(), [
                    'methods'  => $route->getRestMethod(),
                    'callback' => [$route, 'callback'],
                    'args'     => $route->getRestArgs(),
                ]);
            }
        }

        /**
         * @param IRoute $route
         */
        public function registerRoute(IRoute $route) {
            $this->routes[] = $route;
        }
    }
}
