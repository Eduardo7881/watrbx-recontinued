<?php
use watrlabs\router\Routing;

global $router; // IMPORTANT: KEEP THIS HERE!

$router->get('/Game/Join2014.ashx', function(){
    header("Content-type: text/lua");
    require("../storage/2014join.php");
    die();
});

$router->get('/Thumbs/Local.ashx', function(){
    $id = isset($_GET["assetId"]) ? (int)$_GET["assetId"] : 0;
    global $db;
    $thumb = $db->table("thumbnails")->where("assetid", $id)->where("mode", "local")->first();
    if (!$thumb || strpos($thumb->file, "local:") !== 0) {
        http_response_code(404);
        die();
    }
    $path = dirname(__DIR__) . "/storage/thumbnails/" . substr($thumb->file, 6);
    if (!is_file($path)) {
        http_response_code(404);
        die();
    }
    $mime = function_exists("mime_content_type") ? mime_content_type($path) : "image/png";
    header("Content-Type: " . $mime);
    header("Cache-Control: public, max-age=86400");
    readfile($path);
    die();
});

$router->get('/Asset/', function() {
    if(isset($_GET["id"])){
        if(isset($_GET["version"])){
            $version = (int)$_GET["version"];
        } else {
            $version = 0;
        }

        $id = (int)$_GET["id"];

        global $db;

        $asset = $db->table("assets")->where("id", $id)->first();

        if($asset !== null){
            $localPath = dirname(__DIR__) . "/storage/assets/" . basename($asset->fileid);
            if(is_file($localPath)) {
                $mime = function_exists("mime_content_type") ? mime_content_type($localPath) : "application/octet-stream";
                header("Content-Type: " . $mime);
                header("Cache-Control: public, max-age=86400");
                readfile($localPath);
                die();
            }
            header("Location: http://c0.watrbx.xyz/" . $asset->fileid); // evil bucket
            die();
        } else {
            $ch = curl_init("https://assetdelivery.roblox.com/v2/asset/?id=" . urlencode($id) . "&version=" . $version);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Cookie:.ROBLOSECURITY=" . $_ENV["ROBLOSECURITY"],
                "User-Agent: Roblox/WinInet"
            ]);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_USERAGENT => "Roblox/WinInet",
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //curl_close($ch);

            $data = json_decode($response, true);

            if($http_code == 200){
                if (isset($data['locations'][0]['location'])) {
                    header("Location: " . $data['locations'][0]['location'], true, 307);
                    exit;
                } else {
                    http_response_code(500);
                }
            } else {
                die(500);
            }
        }
    }
});

$router->get('/asset/', function() {
    if(isset($_GET["id"])){
        if(isset($_GET["version"])){
            $version = $_GET["version"];
        } else {
            $version = "0";
        }

        $id = $_GET["id"];
	header("Location: /Asset?id=" . $id . "&version=" . $version);
    }
});

$validColor = function($value) {
    $id = (int)$value;
    return ($id > 0 && $id <= 10000) ? $id : 1030;
};

$router->get("/Asset/BodyColors.ashx", function(){
    global $db;
    header("Content-Type: application/xml");
    
    /*
    $head = $_GET['headc'];
    $larm = $_GET['larmc'];
    $lleg = $_GET['llegc'];
    $rarm = $_GET['rarmc'];
    $rleg = $_GET['rlegc'];
    $torso = $_GET['torsoc'];
    */

    // fat boy
    $userId = isset($_GET["userid"]) ? (int)$_GET["userid"] : (isset($_GET["userId"]) ? (int)$_GET["userId"] : 1);
    $user = $db->table("users")->where("id", max(1, $userId))->first();

    $head = $validColor($user->head_color ?? 1030);
    $leftArm = $validColor($user->left_arm_color ?? 1030);
    $leftLeg = $validColor($user->left_leg_color ?? 1030);
    $rightArm = $validColor($user->right_arm_color ?? 1030);
    $rightLeg = $validColor($user->right_leg_color ?? 1030);
    $torso = $validColor($user->torso_color ?? 1030);

    die('<roblox xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation=$_ENV["BASE_URL"] . "/roblox.xsd" version="4">
            <External>null</External>
            <External>nil</External>
            <Item class="BodyColors" referent="RBX2DC0258909A9441E8A72097F024F5FC7">
                <Properties>
                    <int name="HeadColor">' . $head . '</int>
                    <int name="LeftArmColor">' . $leftArm . '</int>
                    <int name="LeftLegColor">' . $leftLeg . '</int>
                    <string name="Name">Body Colors</string>
                    <int name="RightArmColor">' . $rightArm . '</int>
                    <int name="RightLegColor">' . $rightLeg . '</int>
                    <int name="TorsoColor">' . $torso . '</int>
                </Properties>
            </Item>
        </roblox>');
});

$router->post('/game/validate-machine', function(){
    header("Content-type: application/json");
    echo json_encode(array("success"=>true));
    die();
});

$router->get('/GetAllowedSecurityVersions/', function(){
    header("Content-type: application/json");
    die('{"data":[0.235.0pcplayer","0.235.0pcplayer","INTERNALiosapp","0.450.0pcplayer","0.205.0pcplayer"]}');
});

$router->get('/GetAllowedMD5Hashes/', function(){
    //die("True");
    header("Content-type: application/json");
    die('{"data":["f9fa78c9fc7dd435a3fa3b115732f65d", "39dbf64192e20ffc90c76934ccd6b797"]}');
});

$router->get('/game/LoadPlaceInfo.ashx', function(){
    die('local baseUrl = ' . $_ENV["BASE_URL"] . '
pcall(function() game:GetService("SocialService"):SetFriendUrl(baseUrl .. "/Game/LuaWebService/HandleSocialRequest.ashx?method=IsFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetBestFriendUrl(baseUrl .. "/Game/LuaWebService/HandleSocialRequest.ashx?method=IsBestFriendsWith&playerid=%d&userid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupUrl(baseUrl .. "/Game/LuaWebService/HandleSocialRequest.ashx?method=IsInGroup&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRankUrl(baseUrl .. "/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRank&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("SocialService"):SetGroupRoleUrl(baseUrl .. "/Game/LuaWebService/HandleSocialRequest.ashx?method=GetGroupRole&playerid=%d&groupid=%d") end)
pcall(function() game:GetService("GamePassService"):SetPlayerHasPassUrl(baseUrl .. "/Game/GamePass/GamePassHandler.ashx?Action=HasPass&UserID=%d&PassID=%d") end)
pcall(function() game:GetService("MarketplaceService"):SetProductInfoUrl(baseUrl .. "/marketplace/productinfo?assetId=%d") end)
pcall(function() game:GetService("MarketplaceService"):SetDevProductInfoUrl(baseUrl .. "/marketplace/productDetails?productId=%d") end)
pcall(function() game:GetService("MarketplaceService"):SetPlayerOwnsAssetUrl(baseUrl .. "/ownership/hasasset?userId=%d&assetId=%d") end)
pcall(function() game:SetPlaceVersion(1) end)
    ');
});

/*-----------------------------------------------------------------------------------------*/

//
// App Settings
//

$router->get('/Setting/QuietGet/ClientAppSettings/', function(){
    header("Content-type: application/json");
    $json = file_get_contents("../storage/AppSettingsStorage/ClientAppSettings.json");
    die($json);
});

$router->get('/game/players/{userid}/', function($userid){
    header("Content-type: application/json");
    http_response_code(200);
    die('{"ChatFilter":"blacklist"}');
});

$router->get('/Setting/QuietGet/ClientSharedSettings/', function(){
    header("Content-type: application/json");
    $json = file_get_contents("../storage/AppSettingsStorage/ClientAppSettings.json");
    die($json);
});

$router->get('/Setting/QuietGet/AndroidAppSettings/', function() {
    header("Content-type: application/json");
    $json = file_get_contents("../storage/AppSettingsStorage/AndroidAppSettings.json");
    die($json);
});

$router->get('/Setting/QuietGet/iOSAppSettings/', function() {
    header("Content-type: application/json");
    $json = file_get_contents("../storage/AppSettingsStorage/iOSAppSettings.json");
    die($json);
});

$router->get('/Setting/QuietGet/RCCService/', function() {
    header("Content-type: application/json");
    $json = file_get_contents("../storage/rccserviceflags.json");
    die($json);
});

/*-----------------------------------------------------------------------------------------*/

// just so this wont error
$router->get("/Error/Dmp.ashx", function() {
   die();
});
