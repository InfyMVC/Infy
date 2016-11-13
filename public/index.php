<?php
define("INFY_RUN", true);

set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext)
{
    if ($errno === 2)
    {
        if (!array_key_exists('errors', $GLOBALS) || $GLOBALS['errors'] === null)
        {
            $GLOBALS['errors'] = array();
        }

        $entryName = (isset($errcontext["entryName"]) && !is_bool($errcontext["entryName"]) ? $errcontext["entryName"] : "");
        $path      = (isset($errcontext["path"]) ? $errcontext["path"] : "");

        $message = "<div class=\"message\">
        <h1> Error! Include not found!</h1>
        <p> Can't locate $entryName.php under \"$path\"! Please
            check
            your directory structure.</p>
    </div>";


        if (!array_key_exists($entryName, $GLOBALS['errors']))
        {
            $GLOBALS['errors'][$entryName] = $message;
        }
    }
}, E_ALL);

if (array_key_exists('errors', $GLOBALS) && count($GLOBALS['errors']) !== 0)
{
    echo '<!DOCTYPE html >
<html>
<head>
    <title> Include not found </title>
    <meta charset="utf-8"/>
    <meta content="text/html"/>

    <style>
        #content
        {
            font-family: Arial, Helvetica Neue, Helvetica, sans -serif;
            margin:      0 auto;
            width:       50%;
        }

        .message
        {
            margin-left:   auto;
            margin-right:  auto;
            border:        1px solid red;
            margin-bottom: 10px;
            padding:       3%;
        }

        h1
        {
            color:  red;
            margin: 0;
        }

        p
        {
            margin: 10px 0 0 0;
        }
    </style>
</head>
<body>
<div id="content">';

    foreach ($GLOBALS["errors"] as $entryName => $error)
    {
        echo $error;
    }

    echo '</div>
</body>
</html>';

    die();
}

$config = include_once "../app/config/config.php";
$database = include_once "../app/config/database.php";
$routes = include_once "../app/config/routes.php";

$infy = new \Infy\Infy($config, $database, $routes);
$infy->run();