<?php

    namespace Admin\ResuMatch;

    class Router
    {
        private array $routes = [];

        public function get(string $path, callable $handler): void
        {
            $this->routes['GET'][$path] = $handler;
        }

        public function dispatch(string $method, string $uri): void
        {
            $path = parse_url($uri, PHP_URL_PATH);

            // Fast path: exact match (covers every existing route unchanged)
            if (isset($this->routes[$method][$path])) {
                call_user_func($this->routes[$method][$path]);
                return;
            }

            // Param match: only routes containing ':' are checked here, so
            // plain routes never pay this cost and behavior for them is
            // identical to before.
            foreach ($this->routes[$method] ?? [] as $routePath => $handler) {
                if (strpos($routePath, ':') === false) {
                    continue;
                }

                $params = $this->matchParams($routePath, $path);
                if ($params !== null) {
                    call_user_func_array($handler, $params);
                    return;
                }
            }

            http_response_code(404);
            echo "404 - Page not found";
        }

        /**
         * Returns an ordered array of matched param values if $path matches
         * $routePath (e.g. '/results/:id' vs '/results/5' -> ['5']), or
         * null if it doesn't match. Segment count must match exactly, so
         * '/results/:id' does NOT match '/results' or '/results/5/extra'.
         */
        private function matchParams(string $routePath, string $path): ?array
        {
            $routeParts = explode('/', trim($routePath, '/'));
            $pathParts  = explode('/', trim($path, '/'));

            if (count($routeParts) !== count($pathParts)) {
                return null;
            }

            $params = [];
            foreach ($routeParts as $i => $part) {
                if (strpos($part, ':') === 0) {
                    if ($pathParts[$i] === '') {
                        return null;
                    }
                    $params[] = $pathParts[$i];
                } elseif ($part !== $pathParts[$i]) {
                    return null;
                }
            }

            return $params;
        }
    }