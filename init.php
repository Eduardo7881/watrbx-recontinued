<?php
use Pixie\Connection;
use Pixie\QueryBuilder\QueryBuilderHandler;
global $dotenv;
global $db;

(function () {
    // Request Logger
    $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
    $uri = parse_url($_SERVER["REQUEST_URL"] ?? "/", PHP_URL_PATH);
    register_shutdown_function(function() use ($method, $uri) {
        $status = http_response_code();
        if ($status === false) $status = 200;
        $line = sprintf("[%d]: %s %s\n", $status,$method,$uri);
    });
});

spl_autoload_register(function ($class_name) {
    $directory = '../classes/';
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    $file = $directory . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
    else {
        throw new ErrorException("Failed to include class $class_name");
    }
});

// thanks watrabi now i can debug your shit.
// i hate this since it also flags deprecated stuff as a error, which is not the case since they work sometimes.
// because of this i had to patch the whole pixie library
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {

    $config = [
        'driver'    => 'mysql',
        'host'      => $_ENV["DB_HOST"],
        'database'  => $_ENV["DB_NAME"],
        'username'  => $_ENV["DB_USER"],
        'password'  => $_ENV["DB_PASS"],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '', // if you have a prefix for all your tables.
    ];

    $connection = new Connection('mysql', $config);
    $db = $connection->getQueryBuilder(); 
} catch (PDOException $e){
    echo 'oh noes database problems';
    require("../views/really_bad_500.php");
    die();
}
