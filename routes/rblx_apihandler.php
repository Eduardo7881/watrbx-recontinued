<?php
use watrbx\thumbnails;
use watrlabs\router\Routing;
use watrlabs\watrkit\sanitize;
use watrlabs\authentication;
use watrbx\gameserver;
use watrbx\catalog;
use watrbx\RCCServiceSoap;
use Cocur\Slugify\Slugify;
use watrbx\sitefunctions;
use watrbx\relationship\friends;
use asteroid\containers;

global $router; // IMPORTANT: KEEP THIS HERE!
global $db;

/* V1 APIs */
$router->group("/v1", function($router) {
    $router->get("/asset", function() {
        $id = (string)$_GET['id'];
        header("Location: /Asset/?id=" . $id);
        die();
    });
}, '');

/* Data Persistance */
$router->post("/persistence/getV2", function() use ($db) {
    global $db;

    header("Content-Type: application/json");
    $scope = $_POST["qkeys"][0]["scope"] ?? "global";
    $target = $_POST["qkeys"][0]["target"] ?? "";
    $key = $_POST["qkeys"][0]["key"] ?? "";

    if ($target === "" || $key === "") {
        http_response_code(400);
        echo json_encode(["error"=>"missing_key"]);
        return;
    }

    $record = $db->table("datastore")->where("scope", $scope)->where("store_name", $key)->where("target", $target)->first();
    $value = $record->value ?? null;

    echo json_encode([
        "data" => [[
            "Key" => [
                "Scope" => $scope,
                "Target" => $target,
                "Key" => $key
            ],
            "Value" => $value
        ]]
    ]);
});

$router->post("/persistence/set", function() use ($db) {
    global $db;

    header("Content-Type: application/json");

    $scope = $_POST["scope"] ?? "global";
    $target = $_POST["target"] ?? "";
    $key = $_POST["key"] ?? "";
    $value = $_POST["value"] ?? "";

    if ($target === "" || $key === "") {
        http_response_code(400);
        echo json_encode(["error"=>"missing_key"]);
        return;
    }

    $record = $db->table("datastore")->where("scope", $scope)->where("store_name", $key)->where("target", $target)->first();
    if($record) {
        $record->update(["value"=>$value]);
    } else {
        $db->table("datastore")->insert([
            "scope"=>$scope,
            "store_name"=>$key,
            "target"=>$target,
            "value"=>$value
        ]);
    }

    echo json_encode(["success"=>true]);
});

?>
