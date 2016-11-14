<?php

namespace infy;

use Exception;
use infy\web\routing\InfyRouter;

class Infy
{

    private static $instance;
    private $config;
    private $databaseConfig;
    private $routes;
    private $infyRouter;
    private $settings;
    private $htAccessFile;

    function __construct($config, $databaseConfig, $routes, $htAccessFile)
    {
        $this->config         = $config;
        $this->databaseConfig = $databaseConfig;
        $this->routes         = $routes;
        $this->infyRouter     = new InfyRouter();
        $this->htAccessFile   = $htAccessFile;
        $this->instance       = $this;
    }

    public function run()
    {
        if (!isset($this->config) || $this->config == NULL)
        {
            throw new Exception("You need to define the config", 1);
        }

        if (!isset($this->databaseConfig) || $this->databaseConfig == NULL)
        {
            throw new Exception("You need to define the database config", 1);
        }

        if (!isset($this->routes) || $this->routes == NULL)
        {
            throw new Exception("You need to define the routes", 1);
        }

        if (!isset($this->htAccessFile) || $this->htAccessFile == NULL)
        {
            // TODO: Call log to not use basepath from htaccess file
        }

        $this->mapRoutes();

        if (isset($this->htAccessFile) && $this->htAccessFile != NULL && is_string($this->htAccessFile))
        {
            $htAccessFile = file_get_contents($this->htAccessFile);
            $matches      = array();
            preg_match("/RewriteBase (?<basepath>.*)\r\n/", $htAccessFile, $matches);

            $basepath = str_replace(array("\n", "\r", "\r\n"), "", $matches["basepath"]);

            if (substr($basepath, strlen($basepath) - 1) !== "/")
            {
                $basepath .= "/";
            }

            $this->infyRouter->setBasePath($basepath);
        }

        $controllerDirectory = "../app/controller/";
        $this->scanForControllers($controllerDirectory);


        $this->infyRouter->match();
    }

    public function scanForControllers($controllerDirectory)
    {

        $objects = scandir($controllerDirectory);

        foreach ($objects as $object)
        {
            if ($object != "." && $object != "..")
            {
                if (is_dir($object))
                {
                    $this->scanForControllers($object);
                }
                else
                {
                    include_once $controllerDirectory . $object;
                }
            }
        }
    }

    private function mapRoutes()
    {
        foreach ($this->routes as $key => $value)
        {
            $currentRoute = $value;


            if (is_callable($currentRoute[2]))
            {
                $this->infyRouter->map($currentRoute[0], $currentRoute{1}, $currentRoute[2], $currentRoute[3]);
            }
            else
            {
                $this->infyRouter->map($currentRoute[0], $currentRoute{1}, sprintf("%s#%s", $currentRoute[2]['controller'], $currentRoute[2]['action']), $currentRoute[3]);
            }
        }
    }

    public static function getInstance()
    {
        return self::$instance;
    }

}
