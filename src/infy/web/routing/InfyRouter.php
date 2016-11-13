<?php
namespace infy\web\routing;


class InfyRouter
{
    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected $routes = array();
    /**
     * @var array Array of all named routes.
     */
    protected $namedRoutes = array();
    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    private $foundMatch;

    /**
     * @var array
     */
    protected $matchTypes = array(
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    );

    /**
     * @return mixed
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return mixed
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param mixed $routes
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return mixed
     */
    public function getFoundMatch()
    {
        return $this->foundMatch;
    }

    /**
     * @param mixed $foundMatch
     */
    public function setFoundMatch($foundMatch)
    {
        $this->foundMatch = $foundMatch;
    }

    public function map($method, $path, $action, $name)
    {
        if (is_array($this->namedRoutes[$name]))
        {
            throw new \Exception("Cannot redeclare route: " . $name);
        }

        $compiledRoute = $this->compileRoute($path);

        if (strpos($method, "|") !== false)
        {
            $methodParts = explode("|", $method);

            foreach ($methodParts as $methodPart)
            {
                if (is_array($this->namedRoutes[sprintf("%s_%s", $methodPart, $name)]))
                {
                    throw new \Exception("Cannot redeclare route: " . $name);
                }

                if (!is_array($this->routes[$methodPart]))
                {
                    $this->routes[$methodPart] = array();
                }

                $this->namedRoutes[sprintf("%s_%s", $methodPart, $name)] = array(
                    'method' => $methodPart,
                    'path' => $path,
                    'action' => $action,
                    'args' => $compiledRoute['args']
                );

                $this->routes[$methodPart][] = array(
                    'path' => $compiledRoute['compiledRoute'],
                    'action' => $action,
                    'args' => $compiledRoute['args']
                );
            }
        }
        else
        {
            if (!is_array($this->routes[$method]))
            {
                $this->routes[$method] = array();
            }

            $this->namedRoutes[$name] = array(
                'method' => $method,
                'path' => $path,
                'action' => $action,
                'args' => $compiledRoute['args']
            );

            $this->routes[$method][] = array(
                'path' => $compiledRoute['compiledRoute'],
                'action' => $action,
                'args' => $compiledRoute['args']
            );
        }
    }

    /**
     * @param string $route Path from the route
     * @return string
     */
    private function compileRoute($route)
    {
        $retVal = array('compiledRoute' => $route, 'args' => array());

        if (preg_match_all("/\\[(?<matchType>\\w+)\\:(?<name>\\w+)\\]/", $route, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                if (strpos($retVal['compiledRoute'], $matches[0][$i]) !== false)
                {
                    if (!array_key_exists($matches["name"][$i], $retVal['args']))
                    {
                        array_push($retVal['args'], $matches["name"][$i]);
                    }
                    $regex = sprintf("(?<%s>%s)", $matches["name"][$i], $this->matchTypes[$matches["matchType"][$i]]);
                    $retVal['compiledRoute'] = str_replace($matches[0][$i], $regex, $retVal['compiledRoute']);
                }
            }
        }

        return $retVal;
    }

    /**
     * @param string $routeName Name of the route
     * @param array $args Arguments for compiling the route
     * @return string The compiled route
     */
    private function compileNamedroute($routeName = "", $args = array())
    {
        if (!array_key_exists($routeName, $this->namedRoutes))
        {
            throw new \Exception("Cannot find route: " . $routeName);
        }

        $compiledRoute = $this->namedRoutes[$routeName]["path"];

        if (preg_match_all("/\\[(?<matchType>\\w+)\\:(?<name>\\w+)\\]/", $compiledRoute, $matches))
        {
            for ($i = 0; $i < count($matches[0]); $i++)
            {
                if (strpos($compiledRoute, $matches[0][$i]) !== false)
                {
                    $value = (isset($args[$matches["name"][$i]]) ? $args[$matches["name"][$i]] : "");
                    $compiledRoute = str_replace($matches[0][$i], $value, $compiledRoute);
                }
            }
        }

        return $compiledRoute;
    }

    public function match()
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $requestUri = str_replace($this->basePath, "", $_SERVER["REQUEST_URI"]);

        foreach ($this->routes as $key => $value)
        {
            usort($this->routes[$key], function($a, $b)
            {
                return (count($a['args']) == count($b['args']) ? 0 : (count($a['args']) < count($b['args'])) ? 1 : -1);
            });
        }

        // var_dump($this->compileNamedroute("test1", array("id" => 1, "int" => 2, "val" => 3)));

        if (!array_key_exists($requestMethod, $this->routes))
        {
            $this->call404($requestUri);
            return false;
        }

        if ($requestUri === "" || strpos($requestUri, "/") !== 0)
        {
            $requestUri = "/" . $requestUri;
        }

        foreach ($this->routes[$requestMethod] as $key => $value)
        {
            if ($value["path"] === $requestUri)
            {
                $this->callFunction($value['action'], array());
                return true;
            }

            $regex = "/^" . str_replace("/", "\\/", $value["path"]) . "$/";

            if (strpos($value['path'], "(?") !== false && preg_match_all($regex, $requestUri, $matches) !== false)
            {
                $foundAllArguments = true;

                foreach ($value['args'] as $arg)
                {
                    if (!isset($matches[$arg][0]) || !is_string($matches[$arg][0]))
                    {
                        $foundAllArguments = false;
                        break;
                    }
                }

                if ($foundAllArguments)
                {
                    $args = array();

                    foreach ($value['args'] as $key => $arg) {
                        $args[$arg] = $matches[$arg][0];
                    }

                    $this->callFunction($value['action'], $args);

                    return true;
                }
            }
        }
        $this->call404($requestUri);
        return false;
    }

    private function call404($route)
    {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");

        if (array_key_exists("404", $this->namedRoutes))
        {
            if (is_callable($this->namedRoutes["404"]["action"]))
            {
                call_user_func($this->namedRoutes["404"]["action"], $route);
            }
            else
            {
                $this->callFunction($this->namedRoutes["404"]["action"], array('route' => $route));
            }
        }
        else
        {
            echo "404 Route not found: " . $route;
        }
    }

    /**
     * @param string $function Functionstring
     * @param array $args Arguments
     */
    private function callFunction($function = "", $args = array())
    {
        $pos = strpos($function, "#") !== false;

        if ($pos === true)
        {
            $parts = explode("#", $function);

            $controller = new $parts[0]();
            $action = $parts[1];

            call_user_func_array(array($controller, $action), $args);
        }
    }
}