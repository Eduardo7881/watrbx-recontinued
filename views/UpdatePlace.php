<?php
use watrlabs\watrkit\pagebuilder;
use watrlabs\router\Routing;
use watrlabs\authentication;

$router = new Routing();
$pagebuilder = new pagebuilder();
$auth = new authentication();

global $db;
$query = $db->table("universes")->where("id", $id);
$gameinfo = $query->first();

if($gameinfo == null){
    //die("Failed to load game info!");
    $gameinfo = "w"; // testing
}

/*
<link rel="stylesheet" href="/CSS/Base/CSS/FetchCSS?path=main___7000c43d73500e63554d81258494fa21_m.css">
        <link rel="stylesheet" href="/CSS/Base/CSS/FetchCSS?path=leanbase___213b3e760be9513b17fafaa821f394bf_m.css">
        <link rel="stylesheet" href="/CSS/Base/CSS/FetchCSS?path=page___5c81dac2107fac0cca40ea7d6cfd0c89_m.css">
        <link rel="stylesheet" href="/CSS/Base/CSS/FetchCSS?path=page___91ce90e508d798217cc5452e978970d5_m.css">

<script type='text/javascript' src='/js/f49d858ef181e7cd401d8fcb4245e6e8.js.gzip'></script>
<script type='text/javascript' src='/js/745eb2b4d8d8058bd5a17a1fdc8a719a.js'></script>
/js/6b9fed5e91a508780b95c302464d62ef.js.gzip
/js/745eb2b4d8d8058bd5a17a1fdc8a719a.js
*/

$pagebuilder->addresource('cssfiles', '/CSS/Base/CSS/FetchCSS?path=main___7000c43d73500e63554d81258494fa21_m.css');
$pagebuilder->addresource('cssfiles', '/CSS/Base/CSS/FetchCSS?path=leanbase___213b3e760be9513b17fafaa821f394bf_m.css');
$pagebuilder->addresource('cssfiles', '/CSS/Base/CSS/FetchCSS?path=page___5c81dac2107fac0cca40ea7d6cfd0c89_m.css');
$pagebuilder->addresource('cssfiles', '/CSS/Base/CSS/FetchCSS?path=page___91ce90e508d798217cc5452e978970d5_m.css');
$pagebuilder->addresource('jsfiles', '/js/35442da4b07e6a0ed6b085424d1a52cb.js');
$pagebuilder->addresource('jsfiles', '/js/745eb2b4d8d8058bd5a17a1fdc8a719a.js');
$pagebuilder->addresource('jsfiles', '/js/f49d858ef181e7cd401d8fcb4245e6e8.js.gzip');
$pagebuilder->addresource('jsfiles', '/js/6b9fed5e91a508780b95c302464d62ef.js.gzip');
$pagebuilder->set_page_name("Configure Place");

$loggedin = $auth->hasaccount();
$pagebuilder->buildheader();

if (!$loggedin) { header("Location: /newlogin"); die(); }
$placeId = $id; // PHP is really weird....

/*
$query = $db->table("assets")->where("id", $id);
$assetinfo = $query->first();
$gameOwnerId = $gameinfo->owner;
$gameTitle = $gameinfo->title;
$gameDescription = $gameinfo->description;
$gamePublic = $gameinfo->public;

if($assetinfo == null){
    die("Failed to find asset!");
}
$creator = $auth->getuserbyid($gameinfo->owner);

$upvotes = $db->table("likes")->where("assetid", $id)->where("vote", 1)->count();
$downvotes = $db->table("likes")->where("assetid", $id)->where("vote", 0)->count();
*/

$assetinfo = null;
$creator = 1;
$assetinfo = $query->first();
$gameOwnerId = 1;
$gameTitle = "test game";
$gameDescription = "game";
$gamePublic = true;

$userinfo = $auth->getuserinfo($_COOKIE["_ROBLOSECURITY"]);
if ($gameOwnerId !== $userinfo->id) { http_response_code(403); die(); }

?>

<script type="text/javascript">
    // IMPORTANT! If the user is logged in, set to user_id; else, set to ''
    var _user_id = <?=$gameOwnerId?>;

    // IMPORTANT! Set to a unique session ID for the visitor's current browsing session.
    var _session_id = <?=$gameOwnerId?>;

    var _sift = window._sift = window._sift || [];

    // IMPORTANT! Insert your JavaScript snippet key here!
    _sift.push(['_setAccount', '5238aa5d58']);

    _sift.push(['_setUserId', _user_id]);
    _sift.push(['_setSessionId', _session_id]);
    _sift.push(['_trackPageview']);

    (function () {
        function ls() {
            var e = document.createElement('script');
            e.type = 'text/javascript';
            e.async = true;
            e.src = ('https:' === document.location.protocol ? 'https://' : 'https://') + 'cdn.siftscience.com/s.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(e, s);
        }
        if (window.attachEvent) {
            window.attachEvent('onload', ls);
        } else {
            window.addEventListener('load', ls, false);
        }
    }());
</script>

<style type="text/css">
    /* gamesEdit.css is this page's own stylesheet (~CSS/Pages/Places/Update.css, see its
       header): #navbar floats at 156px and #content sits beside it. Its #content width
       of 792px was written for the archived page's 970px container, which is 13px wider
       than this template's content box -- and because gamesEdit.css also gives #content
       overflow:hidden (a block formatting context), a #content that doesn't fit beside
       the float drops below it entirely, tab strip on top and form underneath. So the
       two overrides here pin the strip to gamesEdit's own 156px and let the form take
       whatever width is left. The h1 override is because main___...css makes every h1
       display:inline-block, which sat it beside the float instead of above the page. */
    .ConfigurePlace h1 { display: block; margin-bottom: 10px; }
    .ConfigurePlace .form-label { display: block; margin-bottom: 3px; }
    .ConfigurePlace .field { margin-bottom: 14px; }
    .ConfigurePlace .field .hint { font-size: 11px; color: #666; margin-top: 3px; }
    .ConfigurePlace input.text-box { width: 320px; }
    .ConfigurePlace textarea.text-area { width: 320px; height: 90px; }
    .ConfigurePlace .ConfigureStatus { display: none; margin-bottom: 12px; font-size: 11px; }
    .ConfigurePlace #navbar { float: left; width: 156px; }
    .ConfigurePlace #content { margin-left: 156px; width: auto; }
</style>

<form name="aspnetForm" method="post" action="/places/<?=$placeId?>/update" id="aspnetForm" class="nav-container no-gutter-ads" enctype="multipart/form-data">
<script type="text/javascript">
//<![CDATA[
var theForm = document.forms['aspnetForm'];
if (!theForm) {
    theForm = document.aspnetForm;
}
function __doPostBack(eventTarget, eventArgument) {
    if (!theForm.onsubmit || (theForm.onsubmit() != false)) {
        theForm.__EVENTTARGET.value = eventTarget;
        theForm.__EVENTARGUMENT.value = eventArgument;
        theForm.submit();
    }
}
//]]>
</script>

<div id="BodyWrapper">
    <div id="RepositionBody">
        <div id="Body" style='width:970px;'>
            <div class="boxed-body ConfigurePlace" data-place-id="<?=$placeId?>">
                <h1><?=htmlspecialchars($gameTitle)?></h1>
                <div class="ConfigureStatus"></div>
                <div id="navbar" class="divider-right">
                    <div class="verticaltab selected" data-tab="basicSettings"><a href="#">Basic Settings</a></div>
                    <div class="verticaltab" data-tab="playerAccess"><a href="#">Access</a></div>
                    <div class="verticaltab" data-tab="vipServers"><a href="#">VIP Servers</a></div>
                    <div class="verticaltab" data-tab="gamePasses"><a href="#">Game Passes</a></div>
                    <div class="verticaltab" data-tab="gameMedia"><a href="#">Media</a></div>
                    <div class="verticaltab" data-tab="permissions"><a href="#">Advanced Settings</a></div>
                </div>
                <div id="content">

                    <div id="basicSettings" class="ConfigurePane">
                        <div class="headline"><h2>Basic Settings</h2></div>
                        <div class="field">
                            <label class="form-label" for="title">Name:</label>
                            <input type="text" id="title" class="text-box text-box-medium" maxlength="50" value="<?=htmlspecialchars($gameTitle)?>" />
                        </div>
                        <div class="field">
                            <label class="form-label" for="info">Description:</label>
                            <textarea id="info" class="text-box text-area" maxlength="1000"><?=htmlspecialchars($gameDescription)?></textarea>
                        </div>
                        <div class="field">
                            <label class="form-label" for="genre">Genre:</label>
                            <select class="form-select" id="genre">
                                <option value="All" selected="selected">All</option>
                                <option value="Building">Building</option>
                                <option value="Horror">Horror</option>
                                <option value="TownAndCity">Town and City</option>
                                <option value="Military">Military</option>
                                <option value="Comedy">Comedy</option>
                                <option value="Medieval">Medieval</option>
                                <option value="Adventure">Adventure</option>
                                <option value="SciFi">Sci-Fi</option>
                                <option value="Naval">Naval</option>
                                <option value="FPS">FPS</option>
                                <option value="RPG">RPG</option>
                                <option value="Sports">Sports</option>
                                <option value="Fighting">Fighting</option>
                                <option value="WesternAndCowboy">Western</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="form-label" for="clientyear">Client Year:</label>
                            <select class="form-select" id="clientyear">
                                <option value="2016" selected="selected">2016</option>
                            </select>
                            <div class="hint">Which era of client this game is built for. Shown as a small badge on the game&#039;s icon.</div>
                        </div>
                        <div class="field">
                            <label class="form-label">Update place file:</label>
                            <input type="checkbox" id="replaceplacefile" /><span class="checkboxListItem">Upload a new place file</span>
                            <div id="placefilefield" style="display:none; margin-top:6px;">
                                <input type="file" id="placefile" accept=".rbxl,.rbxlx" style="width:320px;" />
                                <div class="hint">Has to be an .rbxl or .rbxlx that starts with &amp;lt;roblox, 10MB at most. Uploading one rehashes the place, retires its old thumbnail, and is the file players get the next time they join. Your other settings save in the same click.</div>
                            </div>
                        </div>
                        <a class="btn-medium btn-neutral SaveButton">Save</a>
                        <a href="/develop" class="btn-medium btn-negative" style="margin-left:10px;">Cancel</a>
                    </div>
                    <div id="playerAccess" class="ConfigurePane" style="display:none;">
                        <div class="headline"><h2>Access</h2></div>
                        <div class="field" id="devices">
                            <label class="form-label">Playable devices:</label>
                            <input type="checkbox" class="place-device" value="Computer" checked /><label>Computer</label><br>
                            <input type="checkbox" class="place-device" value="Phone" checked /><label>Phone</label><br>
                            <input type="checkbox" class="place-device" value="Tablet" checked /><label>Tablet</label><br>
                        </div>
                        <div class="field">
                            <label class="form-label">Place Type:</label>
                            <ul class="nav nav-pills" id="place-type">
                                <li class="active" data-value="Game"><a>Game Place</a></li>
                                <li data-value="Personal"><a>Personal Server</a></li>
                            </ul>
                        </div>
                        <div class="field">
                            <label class="form-label" for="maxplayers">Number of Players:</label>
                            <select class="form-select" id="maxplayers">
                                <option selected="selected">1</option>
                                <option>2</option>
                                <option>3</option>
                                <option>4</option>
                                <option>5</option>
                                <option>6</option>
                                <option>7</option>
                                <option>8</option>
                                <option>9</option>
                                <option>10</option>
                                <option>11</option>
                                <option>12</option>
                                <option>13</option>
                                <option>14</option>
                                <option>15</option>
                                <option>16</option>
                                <option>17</option>
                                <option>18</option>
                                <option>19</option>
                                <option>20</option>
                                <option>21</option>
                                <option>22</option>
                                <option>23</option>
                                <option>24</option>
                                <option>25</option>
                                <option>26</option>
                                <option>27</option>
                                <option>28</option>
                                <option>29</option>
                                <option>30</option>
                                <option>31</option>
                                <option>32</option>
                                <option>33</option>
                                <option>34</option>
                                <option>35</option>
                                <option>36</option>
                                <option>37</option>
                                <option>38</option>
                                <option>39</option>
                                <option>40</option>
                                <option>41</option>
                                <option>42</option>
                                <option>43</option>
                                <option>44</option>
                                <option>45</option>
                                <option>46</option>
                                <option>47</option>
                                <option>48</option>
                                <option>49</option>
                                <option>50</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="form-label" for="access">Access:</label>
                            <select class="form-select" id="access">
                                <option value="Everyone" selected="selected">Everyone</option>
                                <option value="Friends">Friends</option>
                                <option value="NoOne">No One</option>
                            </select>
                            <div class="hint">Only takes effect while this place isn&#039;t selling access and has VIP Servers turned off.</div>
                        </div>
                        <a class="btn-medium btn-neutral SaveButton">Save</a>
                        <a href="/develop" class="btn-medium btn-negative" style="margin-left:10px;">Cancel</a>
                    </div>

                    <!-- New tab. The two columns behind it (privateserverenabled,
                         privateserverprice) have been in schema.sql all along with no page
                         to set them from. -->
                    <div id="vipServers" class="ConfigurePane" style="display:none;">
                        <div class="headline"><h2>VIP Servers</h2></div>
                        <div class="field">
                            <label class="form-label">Allow VIP Servers:</label>
                            <input type="checkbox" id="privateserverenabled" /><span class="checkboxListItem">Yes</span>
                            <div class="hint">
                                Turning this off deactivates every VIP Server in this game immediately and stops their monthly payments. Their names and allow lists are kept, so turning it back on lets owners renew.                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label" for="privateserverprice">Price per month:</label>
                            <input type="text" id="privateserverprice" class="text-box text-box-medium" maxlength="7" value="100" />
                            <div class="hint">In ROBUX. 0 makes them free. Existing servers are charged the new price at their next renewal.</div>
                        </div>
                        <p></p>
                        <a class="btn-medium btn-neutral SaveButton">Save</a>
                        <a href="/develop" class="btn-medium btn-negative" style="margin-left:10px;">Cancel</a>
                    </div>
                    <!-- Game passes: assets rows with prodcategory 34 tied to this game by
                         `game_passes` (migrations/2026-08-31-game-passes.sql). Created
                         and priced here, bought on their ordinary item pages. -->
                    <div id="gamePasses" class="ConfigurePane" style="display:none;">
                        <div class="headline"><h2>Game Passes</h2></div>
                        <div class="hint">
                            No game passes yet. A pass is a one-time purchase players make to get something extra in this game -- VIP access, a starter pack, a special tool.                        </div>
                        <div class="divider-bottom spacing"></div>
                        <div class="headline"><h2>Create a Game Pass</h2></div>
                        <div class="field">
                            <label class="form-label" for="newpassname">Name:</label>
                            <input type="text" id="newpassname" class="text-box" maxlength="50" />
                        </div>
                        <div class="field">
                            <label class="form-label" for="newpassprice">Price in ROBUX:</label>
                            <input type="text" id="newpassprice" class="text-box" maxlength="7" value="0" />
                            <div class="hint">0 makes the pass free. Players buy passes on the pass&#039;s item page.</div>
                        </div>
                        <div class="field">
                            <label class="form-label" for="newpassdesc">Description:</label>
                            <input type="text" id="newpassdesc" class="text-box" maxlength="200" />
                        </div>
                        <div class="field">
                            <label class="form-label" for="newpassicon">Icon:</label>
                            <input type="file" id="newpassicon" accept="image/png,image/jpeg" style="width:320px;" />
                            <div class="hint">
                                A PNG or JPEG, 2MB at most. Square shows best. Icons are reviewed by a moderator before the pass appears on your game page.                            </div>
                        </div>
                        <a class="btn-medium btn-neutral CreatePassButton">Create</a>
                    </div>
                    <div id="gameMedia" class="ConfigurePane" style="display:none;">
                        <div class="headline"><h2>Media</h2></div>
                        <div class="hint" style="margin-bottom:12px;">
                            Pictures and videos here are what visitors see on your game&#039;s page. Images are reviewed by a moderator first, the same as anything else you upload.                        </div>

                        <div class="headline"><h2>Icon:</h2></div>
                        <div class="field">
                            <div class="hint" style="margin-bottom:8px;">
                                No icon yet, so your game shows a picture of the place itself wherever it is listed. A square image works best.                            </div>
                            <label class="form-label" for="newgameicon">Icon:</label>
                            <input type="file" id="newgameicon" accept="image/png,image/jpeg" style="width:320px;" />
                            <div class="hint">A PNG or JPEG, 4MB at most.</div>
                            <a class="btn-medium btn-neutral UploadIconButton" style="margin-top:6px;">Upload icon</a>
                        </div>

                        <div class="divider-bottom spacing"></div>
                        <div class="headline"><h2>Screenshots</h2></div>
                        <div class="hint">
                            No screenshots yet. Your game page shows one picture of the place until you add some; after that they become a slideshow visitors can flick through.                        </div>
                        <div class="field">
                            <label class="form-label" for="newgameshot">Add a screenshot:</label>
                            <input type="file" id="newgameshot" accept="image/png,image/jpeg" style="width:320px;" />
                            <div class="hint">
                                A PNG or JPEG, 4MB at most. Up to 10 screenshots; wide images (16:9) fit the slideshow without being cropped.                            </div>
                            <a class="btn-medium btn-neutral UploadShotButton" style="margin-top:6px;">Upload screenshot</a>
                        </div>

                        <div class="divider-bottom spacing"></div>
                        <div class="headline"><h2>Video</h2></div>
                        <div class="field">
                            <label class="form-label" for="gamevideo">YouTube link:</label>
                            <input type="text" id="gamevideo" class="text-box" maxlength="200" value="" />
                            <div class="hint">
                                Paste the address from your browser&#039;s bar. The video becomes the first slide of your game&#039;s slideshow and plays there. Clear the box and save to remove it.                            </div>
                            <a class="btn-medium btn-neutral SaveVideoButton" style="margin-top:6px;">Save video</a>
                        </div>
                    </div>
                    <div id="permissions" class="ConfigurePane" style="display:none;">
                        <div class="headline">
                            <h2>Gear Permissions</h2>
                            <img class="TipsyImg tooltip-bottom h2-tooltip" data-toggle="tooltip" height="13" width="12" src="/images/65cb6e4009a00247ca02800047aafb87.png" alt="The type of gear allowed in your place. By default all gears are allowed, however you can choose to allow only a specific gear genre." data-original-title="The type of gear allowed in your place. By default all gears are allowed, however you can choose to allow only a specific gear genre." original-title="">
                        </div>
                        <div class="field">
                            <label class="form-label">Allow Gears:</label>
                            <input type="checkbox" id="allowgears" /><span class="checkboxListItem">Yes</span>
                        </div>
                        <div class="field">
                            <label class="form-label" for="geargenre">Gear Genre:</label>
                            <select class="form-select" id="geargenre">
                                <option value="All" selected="selected">All</option>
                                <option value="Building">Building</option>
                                <option value="Horror">Horror</option>
                                <option value="TownAndCity">Town and City</option>
                                <option value="Military">Military</option>
                                <option value="Comedy">Comedy</option>
                                <option value="Medieval">Medieval</option>
                                <option value="Adventure">Adventure</option>
                                <option value="SciFi">Sci-Fi</option>
                                <option value="Naval">Naval</option>
                                <option value="FPS">FPS</option>
                                <option value="RPG">RPG</option>
                                <option value="Sports">Sports</option>
                                <option value="Fighting">Fighting</option>
                                <option value="WesternAndCowboy">Western</option>
                            </select>
                        </div>
                        <div class="divider-bottom spacing"></div>
                        <div class="headline"><h2>Other Permissions</h2></div>
                        <fieldset>
                            By checking this box, <b>you are granting every other user of the site the right to use</b> (in various ways) the content you are now sharing. <b>If you do not want to grant this right, please do not check this box</b>. For more information about sharing content, please review the <a class="text-link" href="/tos">Terms of Use</a>.                            <p></p>
                            <label>
                                <input type="checkbox" id="allowcopying" /><span class="checkboxListItem">Allow Copying</span>
                            </label>
                        </fieldset>
                        <p></p>
                        <a class="btn-medium btn-neutral SaveButton">Save</a>
                        <a href="/develop" class="btn-medium btn-negative" style="margin-left:10px;">Cancel</a>
                    </div>

                </div>
                <div style="clear:both"></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
// The Save buttons used to call RobloxFrontEnd.editData.updatePermissionsPlace(), which does
// not exist here, so this page has never saved anything. Every pane's Save posts the whole
// form -- the endpoint ignores fields it wasn't sent, but sending all of them means saving
// from the Access tab can't silently drop a genre you set on the first one.
$(function () {

    var PLACE_ID = <?=$placeId?>;
    var TOKEN = "361cbff8d95a87e1d976bb9499dcf726";
    var status = $(".ConfigureStatus");
    var button;

    var say = function (message, ok) {
        status.text(message)
              .removeClass("status-error status-confirm")
              .addClass(ok ? "status-confirm" : "status-error")
              .show();
    };

    // The vertical tab strip.
    $("#navbar .verticaltab").css("cursor", "pointer").on("click", function () {
        var tab = $(this).data("tab");

        $("#navbar .verticaltab").removeClass("selected");
        $(this).addClass("selected");
        $(".ConfigurePane").hide();
        $("#" + tab).show();

        return false;
    });

    $("#place-type li").css("cursor", "pointer").on("click", function () {
        $("#place-type li").removeClass("active");
        $(this).addClass("active");
    });

    var devices = function () {
        var picked = [];

        $(".place-device:checked").each(function () { picked.push($(this).val()); });

        return picked.join(",");
    };

    // The place-file updater: off by default so a Save can't quietly swap the file,
    // and checking it is what reveals the picker.
    $("#replaceplacefile").on("change", function () {
        $("#placefilefield").toggle($(this).is(":checked"));
    });

    var uploadPlaceFile = function (done) {
        var input = $("#placefile")[0];
        var file = input.files && input.files[0];

        if(!file){
            say("Choose a place file to upload, or turn \"Update place file\" off.", false);
            button.removeClass("btn-disabled-neutral");
            return;
        }

        // The server checks this too -- 10MB is what /api/v1/configure-place/upload enforces --
        // but saying it here saves the upload.
        if(file.size > 10 * 1024 * 1024){
            say("That place file is too large. 10MB is the limit.", false);
            button.removeClass("btn-disabled-neutral");
            return;
        }

        var data = new FormData();

        data.append("token", TOKEN);
        data.append("placeId", PLACE_ID);
        data.append("placeFile", file);

        $.ajax({
            url: "/api/v1/configure-place/upload",
            type: "POST",
            data: data,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (upload) {
            done(upload.msg);
        }).fail(function (xhr) {
            var upload = null;

            try { upload = $.parseJSON(xhr.responseText); } catch (e) {}

            say((upload && upload.msg) || "The place file could not be uploaded. Please try again.", false);
            button.removeClass("btn-disabled-neutral");
        });
    };

    $(".SaveButton").on("click", function () {

        button = $(this);

        if (button.hasClass("btn-disabled-neutral")) { return false; }

        button.addClass("btn-disabled-neutral");

        var saveSettings = function (extra) {
            $.post("/api/v1/configure-place", {
                token: TOKEN,
                placeId: PLACE_ID,
                title: $("#title").val(),
                info: $("#info").val(),
                genre: $("#genre").val(),
                access: $("#access").val(),
                placetype: $("#place-type li.active").data("value") || "Game",
                devices: devices(),
                maxplayers: $("#maxplayers").val(),
                allowgears: $("#allowgears").is(":checked") ? "true" : "false",
                geargenre: $("#geargenre").val(),
                clientyear: $("#clientyear").val(),
                allowcopying: $("#allowcopying").is(":checked") ? "true" : "false",
                privateserverenabled: $("#privateserverenabled").is(":checked") ? "true" : "false",
                privateserverprice: $("#privateserverprice").val()
            }, function (data) {
                say((extra ? extra + " " : "") + data.msg, data.success);
                button.removeClass("btn-disabled-neutral");
            }, "json").fail(function () {
                say("Sorry, something went wrong. Please try again.", false);
                button.removeClass("btn-disabled-neutral");
            });
        };

        // The file goes up first: a broken place file should fail before -- not after --
        // the settings it was uploaded alongside are written.
        if ($("#replaceplacefile").is(":checked")) {
            uploadPlaceFile(function (uploadmsg) {
                saveSettings(uploadmsg);
            });
        } else {
            saveSettings();
        }

        return false;
    });

    // The Game Passes tab. Create is a multipart upload, so it is its own request --
    // and it reloads the page on success, because the new pass has to appear in the
    // list above the form.
    $(".CreatePassButton").on("click", function () {

        var create = $(this);

        if (create.hasClass("btn-disabled-neutral")) { return false; }

        if ($("#newpassname").val().trim() === ""){
            say("Please enter a name for your pass.", false);
            return false;
        }

        var picker = $("#newpassicon")[0];

        if(!picker.files || !picker.files[0]){
            say("Please choose an icon for your pass.", false);
            return false;
        }

        var data = new FormData();

        data.append("token", TOKEN);
        data.append("placeId", PLACE_ID);
        data.append("title", $("#newpassname").val());
        data.append("description", $("#newpassdesc").val());
        data.append("price", $("#newpassprice").val());
        data.append("icon", picker.files[0]);

        create.addClass("btn-disabled-neutral");

        $.ajax({
            url: "/api/v1/gamepass-creator",
            type: "POST",
            data: data,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (result) {
            if(result.success){
                say(result.msg + " " + "Reloading...", true);
                window.setTimeout(function () { window.location.reload(); }, 1200);
            } else {
                say(result.msg, false);
                create.removeClass("btn-disabled-neutral");
            }
        }).fail(function (xhr) {
            var result = null;

            try { result = $.parseJSON(xhr.responseText); } catch (e) {}

            say((result && result.msg) || "The game pass could not be created. Please try again.", false);
            create.removeClass("btn-disabled-neutral");
        });

        return false;
    });

    // Editing one pass of the list. Each row posts only its own fields.
    $(".PassSaveButton").on("click", function () {

        var save = $(this);
        var row  = save.closest(".GamePassRow");

        if (save.hasClass("btn-disabled-neutral")) { return false; }

        save.addClass("btn-disabled-neutral");

        $.post("/api/v1/gamepass-update", {
            token: TOKEN,
            placeId: PLACE_ID,
            passId: row.data("pass-id"),
            title: row.find(".PassName").val(),
            description: row.find(".PassDescription").val(),
            price: row.find(".PassPrice").val(),
            forsale: row.find(".PassForSale").is(":checked") ? "true" : "false"
        }, function (data) {
            say(data.msg, data.success);
            save.removeClass("btn-disabled-neutral");
        }, "json").fail(function () {
            say("Sorry, something went wrong. Please try again.", false);
            save.removeClass("btn-disabled-neutral");
        });

        return false;
    });

    /* ---------------------------------------------------------------- Media tab
     *
     * Four endpoints, one shape: post, report, reload when the list on the page has to
     * change. Uploads and removals reload for the same reason the pass creator does --
     * the new row has to appear in the list above the form, and re-rendering it here
     * would mean duplicating the PHP that built it.
     */
    var mediaBusy = false;

    var mediaUpload = function (button, picker, role, noFileMessage) {
        if (mediaBusy) { return; }

        var file = picker.files && picker.files[0];

        if (!file) {
            say(noFileMessage, false);
            return;
        }

        // The server enforces this too; saying it here saves the upload.
        if (file.size > 4 * 1024 * 1024) {
            say("That image is too large. 4MB is the limit.", false);
            return;
        }

        var data = new FormData();

        data.append("token", TOKEN);
        data.append("placeId", PLACE_ID);
        data.append("role", role);
        data.append("image", file);

        mediaBusy = true;
        button.addClass("btn-disabled-neutral");

        $.ajax({
            url: "/api/v1/place-media/upload",
            type: "POST",
            data: data,
            processData: false,
            contentType: false,
            dataType: "json"
        }).done(function (result) {
            if (result.success) {
                say(result.msg + " " + "Reloading...", true);
                window.setTimeout(function () { window.location.reload(); }, 1200);
            } else {
                say(result.msg, false);
                mediaBusy = false;
                button.removeClass("btn-disabled-neutral");
            }
        }).fail(function (xhr) {
            var result = null;

            try { result = $.parseJSON(xhr.responseText); } catch (e) {}

            say((result && result.msg) || "That image could not be uploaded. Please try again.", false);
            mediaBusy = false;
            button.removeClass("btn-disabled-neutral");
        });
    };

    $(".UploadIconButton").on("click", function () {
        mediaUpload($(this), $("#newgameicon")[0], "icon", "Please choose an image for your icon.");
        return false;
    });

    $(".UploadShotButton").on("click", function () {
        mediaUpload($(this), $("#newgameshot")[0], "screenshot", "Please choose an image to add.");
        return false;
    });

    $(document).on("click", ".MediaRemoveButton", function () {
        if (mediaBusy) { return false; }

        var button = $(this);
        var row = button.closest(".GameMediaRow");

        mediaBusy = true;
        button.addClass("btn-disabled-neutral");

        $.post("/api/v1/place-media/remove", {
            token: TOKEN,
            placeId: PLACE_ID,
            mediaId: row.data("media-id")
        }, function (data) {
            if (data.success) {
                say("Removed. Reloading...", true);
                window.setTimeout(function () { window.location.reload(); }, 900);
            } else {
                say(data.msg, false);
                mediaBusy = false;
                button.removeClass("btn-disabled-neutral");
            }
        }, "json").fail(function () {
            say("That image could not be removed. Please try again.", false);
            mediaBusy = false;
            button.removeClass("btn-disabled-neutral");
        });

        return false;
    });

    /* Reordering moves the row in the DOM first and then posts the whole order, so the
     * page reflects the click immediately and one request settles it -- there is no
     * per-row "position" to get out of step with the list. */
    var mediaMove = function (row, up) {
        var sibling = up ? row.prev(".MediaShotRow") : row.next(".MediaShotRow");

        if (sibling.length === 0) { return; }

        if (up) { sibling.before(row); } else { sibling.after(row); }

        var order = $("#MediaShotList .MediaShotRow").map(function () {
            return $(this).data("media-id");
        }).get();

        $.post("/api/v1/place-media/reorder", {
            token: TOKEN,
            placeId: PLACE_ID,
            order: order.join(",")
        }, function (data) {
            say(data.msg, data.success);
        }, "json").fail(function () {
            say("The new order could not be saved. Please try again.", false);
        });
    };

    $(document).on("click", ".MediaUpButton", function () {
        mediaMove($(this).closest(".MediaShotRow"), true);
        return false;
    });

    $(document).on("click", ".MediaDownButton", function () {
        mediaMove($(this).closest(".MediaShotRow"), false);
        return false;
    });

    $(".SaveVideoButton").on("click", function () {
        var button = $(this);

        if (button.hasClass("btn-disabled-neutral")) { return false; }

        button.addClass("btn-disabled-neutral");

        $.post("/api/v1/place-media/video", {
            token: TOKEN,
            placeId: PLACE_ID,
            url: $("#gamevideo").val()
        }, function (data) {
            say(data.msg, data.success);
            button.removeClass("btn-disabled-neutral");
        }, "json").fail(function (xhr) {
            var result = null;

            try { result = $.parseJSON(xhr.responseText); } catch (e) {}

            say((result && result.msg) || "The video could not be saved. Please try again.", false);
            button.removeClass("btn-disabled-neutral");
        });

        return false;
    });
});
</script>
<footer class="wb-footer">
