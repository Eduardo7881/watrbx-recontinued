<?php
/*
I took this from watrabi/rocknet-rblx
Do not say i skidded this since watrabi planned to implement rocknet in this

models for toolbox has to;
- be free
- approved by staff
- public
*/

global $db; // do NOT remove this

$genre = ($_GET["genre"] ?? null);
switch($genre){
    case "GameStuff":
        $ToolboxFetch = $db->prepare("SELECT * FROM assets WHERE approved = '1' AND public = '1' AND free = '1' AND genre = 'GameStuff' AND itemtype = 'model'");
        $ToolboxFetch->execute();
        break;
    case "Weapons":
        $ToolboxFetch = $db->prepare("SELECT * FROM assets WHERE approved = '1' AND public = '1' AND free = '1' AND genre = 'Weapons' AND itemtype = 'model'");
        $ToolboxFetch->execute();
        break;
    default:
        // default would be all
        $ToolboxFetch = $db->prepare("SELECT * FROM assets WHERE approved = '1' AND public = '1' AND free = '1' AND itemtype = 'model'");
        $ToolboxFetch->execute();
       break;

$BoxRows = $ToolboxFetch->fetchAll();
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Toolbox</title>
	<link href=<?=htmlspecialchars($_ENV["BASE_URL"] . "/IDE/Toolbox.css")?> type="text/css" rel="stylesheet">
	<script id="Functions" type="text/javascript">
            const baseUrl = <?=$_ENV["BASE_URL"]?>;

	    function insertContent(id) {
                try {
                    window.external.Insert(baseUrl + "/asset/?id=" + id);
                } catch (e) {
                    alert("Unable to insert item.");
                }
            }

	    function dragRBX(id) {
               try {
                   window.external.StartDrag(baseUrl + "/asset/?id=" + id);
               } catch (e) {
               	   alert("Unable to drag item.");
               }
            }

            function clickButton(e, buttonId) {
               var button = document.getElementById(buttonId);
	       if (typeof bt == "object") {
                   if (navigator.appName.indexOf("Netscape") > (-1)) {
                       if (e.keyCode == 13) { button.click(); return false; }
                   } elseif (navigator.appName.indexOf("Microsoft Internet Explorer") > (-1)) {
                       if (event.keyCode == 13) { button.click(); return false; }
                   }
               }
            }
        </script>
    </head>
    <body class="Page" style="margin: 0;">
        <div id="NewToolboxContainer">
            <div id="ToolboxControls">
                <div id="SetTabs">
                    <a href=<?=htmlspecialchars($_ENV["BASE_URL"] . "/IDE/ClientToolbox.aspx")?> style="text-decoration: none;"><div id="All" class="Tabs">All</div></a>
                    <a href=<?=htmlspecialchars($_ENV["BASE_URL"] . "/IDE/ClientToolbox.aspx?genre=Weapons")?> style="text-decoration: none;"><div id="Weapons" class="Tabs">Weapons</div></a>
		    <a href=<?=htmlspecialchars($_ENV["BASE_URL"] . "/IDE/ClientToolbox.aspx?genre=GameStuff")?> style="text-decoration: none;"><div id="GameStuff" class="Tabs">Game Stuff</div></a>
                </div>
            </div>
            <div id="ToolboxItems">
	        <?php
		    switch(true){
                    	case ($BoxRows):
			    foreach($BoxRows as $Fetch) {
                                echo "
				<div class='ToolboxItem WithoutVoting' id='span_set_item". $Fetch['id'] . "' ondragstart='dragRBX(" . $Fetch['id'] . ")'>
                                    <a href='' onclick='javascript:insertContent(" . $Fetch['id'] . ")' class='itemLink' title='" . $Fetch['name'] . "'>
					<img alt='" . $Fetch['name'] . "' id='img_span_setitem_" . $Fetch['id'] . "' width='60' height='52' src='" . $_ENV['BASE_URL'] . "/Thumbs/Asset.ashx?id=" . $Fetch['id'] . "&x=60&y=62' border='0px' onerror='return Roblox.Controls.Image.OnError(this)'>
				    </a>
                                </div>
                                ";
                            }
			    break;
			default:
			    echo "<p align='center' style='font-family:arial;'>There are no models. ;(</p>";
			    break;
                    }
                ?>
            </div>
        </div>
    </body>
</html>
