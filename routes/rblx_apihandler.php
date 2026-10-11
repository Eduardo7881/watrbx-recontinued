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

/* Root APIs */
$router->post("/abusereport/InGameChatHandler.ashx", function() {
    $post = file_get_contents('php://input');
    if(empty($post)) { http_response_code(500); die("Unknown Error"); }

    $xmlarray = simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
    $encoded = json_encode($xmlarray);
    $decoded = json_decode($encoded, TRUE);
    if (is_array($decoded)) {
        $report = $decoded['@attributes'];
        $userId = (int)$report['userID'];
        $placeID = (int)$report['placeID'];
        $gameJobID = filter_var($report['gameJobID'], FILTER_SANITIZE_FULL_SPECIAL_CHARACTERS, FILTER_FLAG_NO_ENCODE_QUOTES);

        $comments = $decoded['comment'];
        $comments = explode(";", $comments);
        $AbuserID = str_replace("AbuserID:", "", $comments[0]);
        print_r($comments);

        $Type = $comments[1];
        $decodedShit = strstr($decoded['comment'], "User Report");
        $comments = explode("\n", $decodedShit);
        $comments = str_replace(":", "", $comments);
        $UserReport = $comments[1];
        $messages = json_encode($decoded['messages']);

        try {
	    $dbPDO = $db->getPdo();
            $stmt = $dbPDO->prepare("INSERT INTO `reports` (`userID`, `placeID`, `gameJobID`, `AbuserID`, `Type`, `UserReport`, `messages`) VALUES (:userID, :placeID, :gameJobID, :AbuserID, :Type, :UserReport, :messages)");
            $stmt->execute(['userID'=>$userId,'placeID'=>$placeID,'gameJobID'=>$gameJobID,'AbuserID'=>$AbuserID,'Type'=>$Type,'UserReport'=>$UserReport,'messages'=>$messages]);
        } catch (PDOException $e) {
            error_log($e);
            http_response_code(500);
            echo "DataBase Error";
            die();
        }

        echo "Success";
        exit();
    } else {
        http_response_code(500);
        echo "Unknown Error";
        die();
    }
});

$router->get("/currency/balance", function() {
    header("Content-Type: application/json");

    $auth = new authentication();
    $RobuxBalance = 0;
    $TixBalance = 0;

    if($auth->hasaccount()) {
        $userinfo = $auth->getuserinfo($_COOKIE["_ROBLOSECURITY"]);
        $RobuxBalance = $userinfo->robux;
        $TixBalance = $userinfo->tix;
    }

    $rarray = array("robux"=>$RobuxBalance,"tix"=>$TixBalance);
    echo json_encode($rarray);
});

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

/* Misc */
$router->get("/Game/GetAllowedExperimentalFeatures", function() {
    header("Content-Type: application/json");

    $response = json_encode(["HttpAccessAllowed"=>true,"PersistenceAccessAllowed"=>true,"CreatePlaceAllowed"=>false,"PointsServiceAllowed"=>true]);
    echo $response;
    die();
});

$router->get("/Help/Builderman.aspx", function() {
    // What is the purpose of this?
    header("Location: https://en.help.roblox.com");
    die();
});

?>
