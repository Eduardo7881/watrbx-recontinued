<?php
use watrlabs\router\Routing;
use watrlabs\authentication;
use watrlabs\watrkit\pagebuilder;
use watrlabs\watrkit\sanitize;



function checkhelp() {
    echo "hi";
}

global $router; // IMPORTANT: KEEP THIS HERE!

$router->get("/", function() {
    $page = new pagebuilder;
    $page::get_template("index");
});

$router->get('/messages/compose', function(){
    $page = new pagebuilder;
    $page::get_template("compose");
});

$router->get('/IDE/Toolbox.css', function() {
   header("Content-Type: text/css");
   $css = file_get_contents("../storage/css/Toolbox.css");
   die($css);
});

$router->post('/messages/compose', function(){
    if(isset($_POST["subject"]) && isset($_POST["body"]) && isset($_POST["__EVENTTARGET"])){
        $subject = $_POST["subject"];
        $body = $_POST["body"];
        $userid = $_POST["__EVENTTARGET"];

        $auth = new authentication();
        $recipient = $auth->getuserbyid($userid);

        if($auth->hasaccount()){
            $currentuser = $auth->getuserinfo($_COOKIE["_ROBLOSECURITY"]);
            if($recipient !== null){

                global $db;

                

                $msgcount = $db->table("messages")->where("userfrom", $currentuser->id)->where("date", ">", time() - 60)->count();

                if($msgcount >= 3){
                    $page = new pagebuilder;
                    $page::get_template("compose", ["error"=>"You're sending messages too fast!"]);
                } else {
                    $insert = array(
                        "userfrom"=>$currentuser->id,
                        "userto"=>$recipient->id,
                        "subject"=>htmlspecialchars($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        "body"=>htmlspecialchars($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        "date"=>time()
                    );
                    $db->table("messages")->insert($insert);
                    $page = new pagebuilder;
                    $page::get_template("compose", ["success"=>"Message sent to " . $recipient->username . "!"]);
                }
                
            } else {
                $page = new pagebuilder;
                $page::get_template("compose", ["error"=>"Failed to send message."]);
            }
        } else {
            header("Location: /");
            die();
        }

    } else {
        $page = new pagebuilder;
        $page::get_template("compose", ["error"=>"Failed to send message. Are you sure you filled in everything?"]);
    }
});

$router->get("/users/{userid}/friends", function($userid) {
    $page = new pagebuilder;
    $page::get_template("user/friends", ["userid"=>$userid]);
});

$router->get("/Games.aspx", function(){
    header("Location: /games");
    die();
});

$router->get("/temp/create-asset", function(){
    $page = new pagebuilder;
    $page::get_template("temp/uploadasset");
});

$router->get("/temp/universe-creator", function(){
    $page = new pagebuilder;
    $page::get_template("temp/universe-creator");
});

$router->get("/users/{userid}/profile", function($userid) {
    $page = new pagebuilder;
    $page::get_template("user/profile", array("userid"=>$userid));
});

$router->get("/users/{userid}/inventory", function($userid) {
    $page = new pagebuilder;
    $page::get_template("user/inventory");
});

$router->get("/my/messages", function() {
    $page = new pagebuilder;
    $page::get_template("my/messages");
});

$router->get('/MEMBERSHIP/CREATIONDISABLED.aspx', function(){
    $page = new pagebuilder;
    $page::get_template("membership/creationdisabled");
});

$router->get('/Membership/NotApproved.aspx', function(){
    $page = new pagebuilder;
    $page::get_template("membership/notapproved");
});

$router->get("/IDE/ClientToolbox.aspx", function() {
    $page = new pagebuilder;
    $page::get_template("toolbox");
});

$router->get('/admin', function() {
    $auth = new authentication();
    $auth->requiresession();
    $admin = $auth->getuserinfo();
    if (!$admin || (int)$admin->is_admin !== 1) {
        http_response_code(403);
        die('403 - Admin access required.');
    }
    $page = new pagebuilder;
    $page::get_template('admin');
});

$adminGuard = function() {
    $auth = new authentication();
    $auth->requiresession();
    $admin = $auth->getuserinfo();
    if (!$admin || (int)$admin->is_admin !== 1) { http_response_code(403); die("403 - Admin access required."); }
    return $admin;
};

$adminRedirect = function($message = null, $error = null) {
    $params = [];
    if ($message !== null) $params['message'] = $message;
    if ($error !== null) $params['error'] = $error;
    header('Location: /admin' . ($params ? '?' . http_build_query($params) : ''));
    die();
};


/* =====================================================================
 * ADMIN COMMAND TOOLKIT
 * Extra backend commands for the existing Admin Panel.
 * These are intentionally added to the current panel code instead of
 * replacing/rebuilding the panel.
 * ===================================================================== */

$adminJson = function($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    die();
};

$adminPositiveInt = function($value, $default = 0) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return ($value !== false && $value >= 0) ? $value : $default;
};

// Search users without loading the whole user table. Useful for future
// AJAX/admin tools and safe to call only from an authenticated admin.
$router->get('/admin/command/search-users', function() use ($adminGuard, $adminJson) {
    global $db;
    $adminGuard();
    $q = trim($_GET['q'] ?? '');
    if ($q === '') return $adminJson(['users'=>[]]);
    $query = $db->table('users')->where('username', 'LIKE', '%'.$q.'%')->orderBy('id', 'DESC')->limit(25)->get();
    $users = [];
    foreach ($query as $u) {
        $users[] = ['id'=>(int)$u->id, 'username'=>$u->username, 'robux'=>(int)$u->robux, 'tix'=>(int)$u->tix, 'membership'=>$u->membership, 'is_admin'=>(int)$u->is_admin];
    }
    $adminJson(['users'=>$users]);
});

// Reset a user's password using the same password hashing mechanism used by
// the normal account system.
$router->post('/admin/command/reset-password', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $userid = (int)($_POST['userid'] ?? 0);
    $password = (string)($_POST['password'] ?? '');
    $user = $db->table('users')->where('id', $userid)->first();
    if (!$user) $adminRedirect(null, 'User not found.');
    if (strlen($password) < 6) $adminRedirect(null, 'Password must be at least 6 characters.');
    $db->table('users')->where('id', $userid)->update(['password'=>password_hash($password, PASSWORD_DEFAULT)]);
    $adminRedirect('Password reset for '.$user->username.'.');
});

// Force a user to log out from all current sessions.
$router->post('/admin/command/force-logout', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $admin = $adminGuard();
    $userid = (int)($_POST['userid'] ?? 0);
    $user = $db->table('users')->where('id', $userid)->first();
    if (!$user) $adminRedirect(null, 'User not found.');
    $db->table('sessions')->where('author', $userid)->delete();
    $adminRedirect('All sessions revoked for '.$user->username.'.');
});

// Completely remove a user's inventory without deleting the account.
$router->post('/admin/command/clear-inventory', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $userid = (int)($_POST['userid'] ?? 0);
    $user = $db->table('users')->where('id', $userid)->first();
    if (!$user) $adminRedirect(null, 'User not found.');
    $db->table('ownedassets')->where('userid', $userid)->delete();
    $adminRedirect('Inventory cleared for '.$user->username.'.');
});

// Remove one friendship/pending request in either direction.
$router->post('/admin/command/remove-friendship', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $userid = (int)($_POST['userid'] ?? 0);
    $friendid = (int)($_POST['friendid'] ?? 0);
    if ($userid <= 0 || $friendid <= 0 || $userid === $friendid) $adminRedirect(null, 'Invalid user IDs.');
    $db->table('friends')->where('userid', $userid)->where('friendid', $friendid)->delete();
    $db->table('friends')->where('userid', $friendid)->where('friendid', $userid)->delete();
    $adminRedirect('Friendship/request removed.');
});

// Broadcast a private message to every account.
$router->post('/admin/command/broadcast-message', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $admin = $adminGuard();
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($subject === '' || $body === '') $adminRedirect(null, 'Subject and body are required.');
    $subject = htmlspecialchars(substr($subject, 0, 255), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $body = htmlspecialchars($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $count = 0;
    foreach ($db->table('users')->get() as $user) {
        $db->table('messages')->insert(['userfrom'=>(int)$admin->id,'userto'=>(int)$user->id,'subject'=>$subject,'body'=>$body,'date'=>time(),'hasread'=>0]);
        $count++;
    }
    $adminRedirect('Broadcast sent to '.$count.' users.');
});

// Create a site-wide feed announcement using the existing feed table.
$router->post('/admin/command/create-announcement', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $admin = $adminGuard();
    $content = trim($_POST['content'] ?? '');
    if ($content === '') $adminRedirect(null, 'Announcement text is required.');
    $db->table('feed')->insert(['content'=>htmlspecialchars(substr($content, 0, 2000), ENT_QUOTES | ENT_HTML5, 'UTF-8'),'owner'=>(int)$admin->id,'date'=>time()]);
    $adminRedirect('Announcement published.');
});

// Remove old feed entries. Keeps the newest N posts.
$router->post('/admin/command/prune-feed', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $keep = max(1, min(5000, (int)($_POST['keep'] ?? 100)));
    $rows = $db->table('feed')->orderBy('id', 'DESC')->get();
    $deleted = 0;
    $index = 0;
    foreach ($rows as $row) {
        if ($index++ < $keep) continue;
        $db->table('feed')->where('id', (int)$row->id)->delete();
        $deleted++;
    }
    $adminRedirect('Pruned '.$deleted.' old feed entries.');
});

// Clear expired login/captcha/session records.
$router->post('/admin/command/cleanup', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $now = time();
    $sessions = $db->table('sessions')->where('expiration', '<=', $now)->delete();
    $captcha = $db->table('captchaverified')->where('time', '<=', $now)->delete();
    $adminRedirect('Cleanup complete. Expired sessions/captcha records removed.');
});

// Remove old request/page logs. Keeps the newest N rows.
$router->post('/admin/command/prune-logs', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $keep = max(1, min(100000, (int)($_POST['keep'] ?? 5000)));
    $rows = $db->table('logs')->orderBy('id', 'DESC')->get();
    $deleted = 0;
    $index = 0;
    foreach ($rows as $row) {
        if ($index++ < $keep) continue;
        $db->table('logs')->where('id', (int)$row->id)->delete();
        $deleted++;
    }
    $adminRedirect('Pruned '.$deleted.' old logs.');
});

// Change several catalog properties at once.
$router->post('/admin/command/update-asset', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $assetid = (int)($_POST['assetid'] ?? 0);
    $asset = $db->table('assets')->where('id', $assetid)->first();
    if (!$asset) $adminRedirect(null, 'Asset not found.');
    $allowed = ['robux','tix','status','publicdomain','rating','remaining'];
    $update = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $_POST) && $_POST[$field] !== '') {
            $value = (int)$_POST[$field];
            if (in_array($field, ['robux','tix','rating','remaining'], true)) $value = max(0, $value);
            $update[$field] = $value;
        }
    }
    if (!$update) $adminRedirect(null, 'No asset fields were supplied.');
    $update['updated'] = time();
    $db->table('assets')->where('id', $assetid)->update($update);
    $adminRedirect('Asset #'.$assetid.' updated.');
});

// Reset sales counter without deleting the item.
$router->post('/admin/command/reset-sales', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $assetid = (int)($_POST['assetid'] ?? 0);
    $asset = $db->table('assets')->where('id', $assetid)->first();
    if (!$asset) $adminRedirect(null, 'Asset not found.');
    $db->table('assets')->where('id', $assetid)->update(['sales'=>0,'updated'=>time()]);
    $adminRedirect('Sales counter reset for asset #'.$assetid.'.');
});

// Clear all ratings/votes for an asset.
$router->post('/admin/command/clear-likes', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $assetid = (int)($_POST['assetid'] ?? 0);
    $asset = $db->table('assets')->where('id', $assetid)->first();
    if (!$asset) $adminRedirect(null, 'Asset not found.');
    $db->table('likes')->where('assetid', $assetid)->delete();
    $adminRedirect('Votes cleared for asset #'.$assetid.'.');
});

// Update a game's public settings without touching its place asset.
$router->post('/admin/command/update-universe', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $id = (int)($_POST['id'] ?? 0);
    $game = $db->table('universes')->where('id', $id)->first();
    if (!$game) $adminRedirect(null, 'Universe not found.');
    $update = [];
    if (isset($_POST['title'])) $update['title'] = htmlspecialchars(substr(trim($_POST['title']), 0, 255), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (isset($_POST['description'])) $update['description'] = htmlspecialchars(substr(trim($_POST['description']), 0, 5000), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (isset($_POST['maxplayers'])) $update['maxplayers'] = max(1, min(200, (int)$_POST['maxplayers']));
    if (isset($_POST['public'])) $update['public'] = (int)((bool)$_POST['public']);
    if (isset($_POST['privateserverenabled'])) $update['privateserverenabled'] = (int)((bool)$_POST['privateserverenabled']);
    if (isset($_POST['privateserverprice'])) $update['privateserverprice'] = max(0, (int)$_POST['privateserverprice']);
    if (!$update) $adminRedirect(null, 'No universe fields were supplied.');
    $db->table('universes')->where('id', $id)->update($update);
    $adminRedirect('Universe #'.$id.' updated.');
});

// Delete a universe and its visit records.
$router->post('/admin/command/delete-universe', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $id = (int)($_POST['id'] ?? 0);
    if (!$db->table('universes')->where('id', $id)->first()) $adminRedirect(null, 'Universe not found.');
    $db->table('visits')->where('universeid', $id)->delete();
    $db->table('badges')->where('universeid', $id)->delete();
    $db->table('universes')->where('id', $id)->delete();
    $adminRedirect('Universe #'.$id.' deleted.');
});

// Job/server maintenance commands.
$router->post('/admin/command/job-action', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $jobid = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $job = $db->table('jobs')->where('id', $jobid)->first();
    if (!$job) $adminRedirect(null, 'Job not found.');
    if ($action === 'delete') {
        $db->table('jobs')->where('id', $jobid)->delete();
        $adminRedirect('Job #'.$jobid.' deleted.');
    }
    if ($action === 'stop') {
        $db->table('jobs')->where('id', $jobid)->update(['status'=>'0']);
        $adminRedirect('Job #'.$jobid.' stopped.');
    }
    if ($action === 'reset') {
        $db->table('jobs')->where('id', $jobid)->update(['status'=>'0','rccinstance'=>null]);
        $adminRedirect('Job #'.$jobid.' reset.');
    }
    $adminRedirect(null, 'Unknown job action.');
});

// Revoke an API key immediately.
$router->post('/admin/command/revoke-apikey', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $id = (int)($_POST['id'] ?? 0);
    if (!$db->table('apikeys')->where('id', $id)->first()) $adminRedirect(null, 'API key not found.');
    $db->table('apikeys')->where('id', $id)->delete();
    $adminRedirect('API key #'.$id.' revoked.');
});

// Remove a server registration from the local server registry.
$router->post('/admin/command/delete-server', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $adminGuard();
    $id = (int)($_POST['id'] ?? 0);
    if (!$db->table('servers')->where('id', $id)->first()) $adminRedirect(null, 'Server not found.');
    $db->table('servers')->where('id', $id)->delete();
    $adminRedirect('Server #'.$id.' removed from the registry.');
});

// Remove a user's sessions, inventory, friend links and moderation records
// while keeping the account itself.
$router->post('/admin/command/reset-account-state', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $admin = $adminGuard();
    $userid = (int)($_POST['userid'] ?? 0);
    if ($userid === (int)$admin->id) $adminRedirect(null, 'You cannot reset your own account state.');
    $user = $db->table('users')->where('id', $userid)->first();
    if (!$user) $adminRedirect(null, 'User not found.');
    $db->table('sessions')->where('author', $userid)->delete();
    $db->table('ownedassets')->where('userid', $userid)->delete();
    $db->table('friends')->where('userid', $userid)->delete();
    $db->table('friends')->where('friendid', $userid)->delete();
    $db->table('moderation')->where('userid', $userid)->delete();
    $adminRedirect('Account state reset for '.$user->username.'.');
});



/* =====================================================================
 * ADMIN CONTROL PANEL COMMANDS
 * These are real panel operations. They do not navigate the administrator
 * away from /admin and operate directly on the existing database tables.
 * ===================================================================== */

$router->post('/admin/command/set-currency', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0);
    $user=$db->table('users')->where('id',$userid)->first();
    if(!$user) $adminRedirect(null,'User not found.');
    $robux=max(0,(int)($_POST['robux']??0)); $tix=max(0,(int)($_POST['tix']??0));
    $db->table('users')->where('id',$userid)->update(['robux'=>$robux,'tix'=>$tix]);
    $adminRedirect('Currency set for '.$user->username.'.');
});

$router->post('/admin/command/update-user-full', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0); $user=$db->table('users')->where('id',$userid)->first();
    if(!$user) $adminRedirect(null,'User not found.');
    $update=[];
    if(isset($_POST['username']) && trim($_POST['username'])!=='') $update['username']=substr(trim($_POST['username']),0,255);
    if(array_key_exists('gender',$_POST)) $update['gender']=($_POST['gender']===''?null:(int)$_POST['gender']);
    if(array_key_exists('membership',$_POST) && in_array($_POST['membership'],['None','BuildersClub','TurboBuildersClub','OutrageousBuildersClub'],true)) $update['membership']=$_POST['membership'];
    if(isset($_POST['blurb'])) $update['blurb']=substr(trim($_POST['blurb']),0,1000);
    if(!$update) $adminRedirect(null,'No user fields supplied.');
    $db->table('users')->where('id',$userid)->update($update);
    $adminRedirect('User profile updated.');
});

$router->post('/admin/command/delete-user-messages', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0); $user=$db->table('users')->where('id',$userid)->first();
    if(!$user) $adminRedirect(null,'User not found.');
    $db->table('messages')->where('userfrom',$userid)->delete();
    $db->table('messages')->where('userto',$userid)->delete();
    $adminRedirect('All messages involving '.$user->username.' were deleted.');
});

$router->post('/admin/command/clear-friends', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0); $user=$db->table('users')->where('id',$userid)->first();
    if(!$user) $adminRedirect(null,'User not found.');
    $db->table('friends')->where('userid',$userid)->delete(); $db->table('friends')->where('friendid',$userid)->delete();
    $adminRedirect('All friendships removed for '.$user->username.'.');
});

$router->post('/admin/command/revoke-owned-asset', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0); $assetid=(int)($_POST['assetid']??0);
    if(!$db->table('users')->where('id',$userid)->first()) $adminRedirect(null,'User not found.');
    if(!$db->table('assets')->where('id',$assetid)->first()) $adminRedirect(null,'Asset not found.');
    $db->table('ownedassets')->where('userid',$userid)->where('assetid',$assetid)->delete();
    $adminRedirect('Asset ownership revoked.');
});

$router->post('/admin/command/transfer-asset', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $from=(int)($_POST['from_userid']??0); $to=(int)($_POST['to_userid']??0); $assetid=(int)($_POST['assetid']??0);
    if($from===$to || $from<=0 || $to<=0) $adminRedirect(null,'Invalid source/destination user.');
    if(!$db->table('users')->where('id',$from)->first() || !$db->table('users')->where('id',$to)->first()) $adminRedirect(null,'User not found.');
    if(!$db->table('assets')->where('id',$assetid)->first()) $adminRedirect(null,'Asset not found.');
    $owned=$db->table('ownedassets')->where('userid',$from)->where('assetid',$assetid)->first();
    if(!$owned) $adminRedirect(null,'Source user does not own that asset.');
    $already=$db->table('ownedassets')->where('userid',$to)->where('assetid',$assetid)->first();
    if($already) $adminRedirect(null,'Destination user already owns that asset.');
    $db->table('ownedassets')->where('id',$owned->id)->update(['userid'=>$to,'time'=>time()]);
    $adminRedirect('Asset ownership transferred.');
});

$router->post('/admin/command/delete-thumbnail', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $id=(int)($_POST['id']??0); $thumb=$db->table('thumbnails')->where('id',$id)->first();
    if(!$thumb) $adminRedirect(null,'Thumbnail not found.');
    $path=dirname(__DIR__).'/storage/thumbnails/'.basename($thumb->file);
    if(is_file($path)) @unlink($path);
    $db->table('thumbnails')->where('id',$id)->delete();
    $adminRedirect('Thumbnail #'.$id.' deleted.');
});

$router->post('/admin/command/create-badge', function() use ($adminGuard, $adminRedirect) {
    global $db; $admin=$adminGuard();
    $title=trim($_POST['title']??''); $description=trim($_POST['description']??''); $universeid=(int)($_POST['universeid']??0);
    if($title==='') $adminRedirect(null,'Badge title is required.');
    if(!$db->table('universes')->where('id',$universeid)->first()) $adminRedirect(null,'Universe not found.');
    $db->table('badges')->insert(['title'=>substr($title,0,255),'description'=>substr($description,0,2000),'universeid'=>$universeid,'owner'=>(int)$admin->id]);
    $adminRedirect('Badge created.');
});

$router->post('/admin/command/delete-badge', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $id=(int)($_POST['id']??0);
    if(!$db->table('badges')->where('id',$id)->first()) $adminRedirect(null,'Badge not found.');
    $db->table('badges')->where('id',$id)->delete(); $adminRedirect('Badge #'.$id.' deleted.');
});

$router->post('/admin/command/update-group', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $id=(int)($_POST['id']??0); $group=$db->table('groups')->where('id',$id)->first();
    if(!$group) $adminRedirect(null,'Group not found.');
    $update=[];
    if(isset($_POST['title'])) $update['title']=substr(trim($_POST['title']),0,255);
    if(isset($_POST['description'])) $update['description']=substr(trim($_POST['description']),0,5000);
    if(isset($_POST['robux'])) $update['robux']=max(0,(int)$_POST['robux']);
    if(isset($_POST['tix'])) $update['tix']=max(0,(int)$_POST['tix']);
    $db->table('groups')->where('id',$id)->update($update); $adminRedirect('Group #'.$id.' updated.');
});

$router->post('/admin/command/delete-group', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $id=(int)($_POST['id']??0);
    if(!$db->table('groups')->where('id',$id)->first()) $adminRedirect(null,'Group not found.');
    $db->table('groups')->where('id',$id)->delete(); $adminRedirect('Group #'.$id.' deleted.');
});

$router->post('/admin/command/reset-universe-visits', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $id=(int)($_POST['id']??0);
    if(!$db->table('universes')->where('id',$id)->first()) $adminRedirect(null,'Universe not found.');
    $db->table('visits')->where('universeid',$id)->delete(); $adminRedirect('Visit history reset for universe #'.$id.'.');
});

$router->post('/admin/command/clear-place-servers', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $placeid=(int)($_POST['placeid']??0);
    $db->table('activeplayers')->where('placeid',$placeid)->delete();
    $db->table('game_instances')->where('placeid',$placeid)->delete();
    $db->table('join_codes')->where('placeid',$placeid)->delete();
    $adminRedirect('Runtime instances cleared for place #'.$placeid.'.');
});

$router->post('/admin/command/delete-rcc-instance', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $id=(int)($_POST['id']??0);
    if(!$db->table('rccinstances')->where('id',$id)->first()) $adminRedirect(null,'RCC instance not found.');
    $db->table('rccinstances')->where('id',$id)->delete(); $adminRedirect('RCC instance #'.$id.' removed.');
});

$router->post('/admin/command/clear-join-codes', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $db->table('join_codes')->delete(); $adminRedirect('All temporary join codes cleared.');
});

$router->post('/admin/command/clear-expired-apikeys', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $now=time();
    $db->table('apikeys')->where('expiration','<=',$now)->delete(); $adminRedirect('Expired API keys removed.');
});

$router->post('/admin/command/clear-maintcodes', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $db->table('maintcodes')->delete(); $adminRedirect('All maintenance codes cleared.');
});

$router->post('/admin/command/create-maintcode', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $code=trim($_POST['code']??''); $discord=(int)($_POST['discord_user']??0); $expires=max(time()+60,(int)($_POST['expires']??0));
    if($code==='') $adminRedirect(null,'Maintenance code is required.');
    $db->table('maintcodes')->insert(['code'=>substr($code,0,255),'discord_user'=>$discord,'expires'=>$expires]);
    $adminRedirect('Maintenance code created.');
});

$router->post('/admin/command/clear-user-moderation', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $userid=(int)($_POST['userid']??0);
    if(!$db->table('users')->where('id',$userid)->first()) $adminRedirect(null,'User not found.');
    $db->table('moderation')->where('userid',$userid)->delete(); $adminRedirect('Moderation history cleared.');
});

$router->post('/admin/grant-currency', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0); $robux=(int)($_POST['robux']??0); $tix=(int)($_POST['tix']??0);
    $user=$db->table('users')->where('id',$userid)->first();
    if(!$user || $robux<0 || $tix<0 || ($robux===0 && $tix===0)) $adminRedirect(null,'Invalid user or amount.');
    $db->table('users')->where('id',$userid)->update(['robux'=>(int)$user->robux+$robux,'tix'=>(int)$user->tix+$tix]);
    $adminRedirect('Currency granted to '.$user->username.'.');
});

$router->post('/admin/set-membership', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $userid=(int)($_POST['userid']??0); $membership=$_POST['membership']??'None';
    $allowed=['None','BuildersClub','TurboBuildersClub','OutrageousBuildersClub'];
    if(!in_array($membership,$allowed,true)) $adminRedirect(null,'Invalid membership.');
    $user=$db->table('users')->where('id',$userid)->first(); if(!$user) $adminRedirect(null,'User not found.');
    $db->table('users')->where('id',$userid)->update(['membership'=>$membership]); $adminRedirect('Membership updated for '.$user->username.'.');
});

$router->post('/admin/set-admin', function() use ($adminGuard, $adminRedirect) {
    global $db; $admin=$adminGuard(); $userid=(int)($_POST['userid']??0); $isAdmin=(int)($_POST['is_admin']??0);
    if($userid===(int)$admin->id && $isAdmin===0) $adminRedirect(null,'You cannot remove your own admin permission from this panel.');
    $user=$db->table('users')->where('id',$userid)->first(); if(!$user) $adminRedirect(null,'User not found.');
    $db->table('users')->where('id',$userid)->update(['is_admin'=>$isAdmin?1:0]); $adminRedirect('Admin permission updated.');
});

$router->post('/admin/grant-asset', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $userid=(int)($_POST['userid']??0); $assetid=(int)($_POST['assetid']??0);
    $user=$db->table('users')->where('id',$userid)->first(); $asset=$db->table('assets')->where('id',$assetid)->first();
    if(!$user || !$asset) $adminRedirect(null,'User or asset not found.');
    $owned=$db->table('ownedassets')->where('userid',$userid)->where('assetid',$assetid)->first();
    if($owned) $adminRedirect(null,'User already owns this asset.');
    $db->table('ownedassets')->insert(['userid'=>$userid,'assetid'=>$assetid,'time'=>time()]); $adminRedirect('Granted asset #'.$assetid.' to '.$user->username.'.');
});

$router->post('/admin/moderate', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $userid=(int)($_POST['userid']??0); $action=$_POST['action']??'warning'; $days=max(1,min(3650,(int)($_POST['days']??1))); $note=trim($_POST['note']??'');
    $user=$db->table('users')->where('id',$userid)->first(); if(!$user || $note==='') $adminRedirect(null,'User and moderation note are required.');
    $isBan=$action==='ban';
    $db->table('moderation')->insert(['userid'=>$userid,'type'=>$isBan?'days':'warning','reviewed'=>0,'banneduntil'=>$isBan?time()+($days*86400):time(),'moderatornote'=>substr($note,0,255),'offensiveitem'=>null,'days'=>$isBan?$days:null,'canignore'=>$isBan?0:1]);
    $adminRedirect($isBan?'User banned for '.$days.' day(s).':'Warning added to '.$user->username.'.');
});

$router->post('/admin/send-message', function() use ($adminGuard, $adminRedirect) {
    global $db; $admin=$adminGuard(); $userid=(int)($_POST['userid']??0); $subject=trim($_POST['subject']??''); $body=trim($_POST['body']??'');
    $user=$db->table('users')->where('id',$userid)->first(); if(!$user || $subject==='' || $body==='') $adminRedirect(null,'Recipient, subject and body are required.');
    $db->table('messages')->insert(['userfrom'=>(int)$admin->id,'userto'=>$userid,'subject'=>htmlspecialchars($subject,ENT_QUOTES|ENT_HTML5,'UTF-8'),'body'=>htmlspecialchars($body,ENT_QUOTES|ENT_HTML5,'UTF-8'),'date'=>time(),'hasread'=>0]);
    $adminRedirect('Message sent to '.$user->username.'.');
});

$router->post('/admin/delete-user', function() use ($adminGuard, $adminRedirect) {
    global $db; $admin=$adminGuard(); $userid=(int)($_POST['userid']??0); if($userid===(int)$admin->id) $adminRedirect(null,'You cannot delete yourself.');
    $user=$db->table('users')->where('id',$userid)->first(); if(!$user) $adminRedirect(null,'User not found.');
    $db->table('ownedassets')->where('userid',$userid)->delete(); $db->table('messages')->where('userfrom',$userid)->delete(); $db->table('messages')->where('userto',$userid)->delete(); $db->table('moderation')->where('userid',$userid)->delete(); $db->table('users')->where('id',$userid)->delete();
    $adminRedirect('Deleted user '.$user->username.'.');
});

$router->post('/admin/delete-asset', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard(); $assetid=(int)($_POST['assetid']??0); $asset=$db->table('assets')->where('id',$assetid)->first(); if(!$asset) $adminRedirect(null,'Asset not found.');
    $db->table('ownedassets')->where('assetid',$assetid)->delete(); $db->table('thumbnails')->where('assetid',$assetid)->delete(); $path=dirname(__DIR__).'/storage/assets/'.basename($asset->fileid); if(is_file($path)) @unlink($path); $db->table('assets')->where('id',$assetid)->delete();
    $adminRedirect('Deleted asset #'.$assetid.'.');
});



$router->post('/admin/update-user', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $userid=(int)($_POST['userid']??0);
    $user=$db->table('users')->where('id',$userid)->first();
    if(!$user) $adminRedirect(null,'User not found.');
    $email=trim($_POST['email']??''); $blurb=trim($_POST['blurb']??'');
    $db->table('users')->where('id',$userid)->update([
        'email'=>$email === '' ? null : substr($email,0,255),
        'blurb'=>substr($blurb,0,1000)
    ]);
    $adminRedirect('Profile updated for '.$user->username.'.');
});

$router->post('/admin/toggle-asset', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $assetid=(int)($_POST['assetid']??0); $field=$_POST['field']??'';
    if(!in_array($field,['featured','limited','limitedu'],true)) $adminRedirect(null,'Invalid catalog flag.');
    $asset=$db->table('assets')->where('id',$assetid)->first();
    if(!$asset) $adminRedirect(null,'Asset not found.');
    $value=(int)!((int)$asset->{$field});
    $db->table('assets')->where('id',$assetid)->update([$field=>$value,'updated'=>time()]);
    $adminRedirect('Catalog flag updated for asset #'.$assetid.'.');
});

$router->post('/admin/clear-moderation', function() use ($adminGuard, $adminRedirect) {
    global $db; $adminGuard();
    $id=(int)($_POST['id']??0);
    if(!$db->table('moderation')->where('id',$id)->first()) $adminRedirect(null,'Moderation record not found.');
    $db->table('moderation')->where('id',$id)->delete();
    $adminRedirect('Moderation record cleared.');
});

$router->post('/admin/create-asset', function() use ($adminGuard, $adminRedirect) {
    global $db;
    $admin=$adminGuard();
    $name=trim($_POST['name']??'');
    $description=trim($_POST['description']??'');
    $category=(int)($_POST['prodcategory']??2);
    $robux=max(0,(int)($_POST['robux']??0));
    $tix=max(0,(int)($_POST['tix']??0));
    $remaining=max(0,(int)($_POST['remaining']??0));
    $memlevel=max(0,(int)($_POST['memlevel']??0));
    if($name==='') $adminRedirect(null,'Asset name is required.');
    $dir=dirname(__DIR__).'/storage/assets';
    $thumbdir=dirname(__DIR__).'/storage/thumbnails';
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    if(!is_dir($thumbdir)) @mkdir($thumbdir,0775,true);
    if(!is_dir($dir) || !is_writable($dir)) $adminRedirect(null,'storage/assets is not writable.');
    $fileid=md5($name.'|'.microtime(true).'|'.random_int(1,PHP_INT_MAX));
    $stored=false;
    if(isset($_FILES['asset']) && isset($_FILES['asset']['error']) && $_FILES['asset']['error']===UPLOAD_ERR_OK){
        $tmp=$_FILES['asset']['tmp_name'];
        $fileid=md5_file($tmp) ?: $fileid;
        $destination=$dir.'/'.$fileid;
        if(!move_uploaded_file($tmp,$destination)) $adminRedirect(null,'Could not store the uploaded asset.');
        $stored=true;
    }
    $assetFile='local:'.$fileid;
    $now=time();
    $insertid=$db->table('assets')->insert([
        'prodtype'=>'User Product','prodcategory'=>$category,'name'=>htmlspecialchars($name,ENT_QUOTES|ENT_HTML5,'UTF-8'),
        'description'=>htmlspecialchars($description,ENT_QUOTES|ENT_HTML5,'UTF-8'),'asseticon'=>null,'created'=>$now,'updated'=>$now,
        'robux'=>$robux,'tix'=>$tix,'sales'=>0,'publicdomain'=>0,'featured'=>isset($_POST['featured'])?1:0,
        'limited'=>isset($_POST['limited'])?1:0,'limitedu'=>isset($_POST['limitedu'])?1:0,'remaining'=>$remaining>0?$remaining:null,
        'memlevel'=>$memlevel,'rating'=>null,'status'=>0,'owner'=>(int)$admin->id,'fileid'=>$assetFile
    ]);
    if(!$insertid){ if($stored && is_file($dir.'/'.$fileid)) @unlink($dir.'/'.$fileid); $adminRedirect(null,'Could not create catalog asset.'); }
    if(isset($_FILES['thumbnail']) && isset($_FILES['thumbnail']['error']) && $_FILES['thumbnail']['error']===UPLOAD_ERR_OK){
        $tmp=$_FILES['thumbnail']['tmp_name'];
        $info=@getimagesize($tmp);
        $mime=$info['mime']??'';
        $allowed=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
        if(isset($allowed[$mime])){
            $thumbFile=$insertid.'-300x300.'.$allowed[$mime];
            if(move_uploaded_file($tmp,$thumbdir.'/'.$thumbFile)){
                $db->table('thumbnails')->insert(['dimensions'=>'300x300','assetid'=>(int)$insertid,'userid'=>null,'mode'=>'local','file'=>'local:'.$thumbFile]);
            }
        }
    }
    $adminRedirect('Created catalog asset #'.$insertid.($stored?' with local asset data.':' without an asset file.'));
});

$router->get('/{slug}-item', function($thing){
    $router = new Routing();

    if(isset($_GET["id"])){
        $id = (int)$_GET["id"];
        global $db;

        $asset = $db->table("assets")->where("id", $id)->first();

        if($asset !== null){
            $page = new pagebuilder;
            $page::get_template("item", array("asset"=>$asset));
        } else {
            //die($router->return_status(404));
	    $router->return_status(404);
            exit;
        }

    } else {
        //die($router->return_status(404));
        $router->return_status(404);
        exit;
    }

});

$router->get('/Upgrades/BuildersClubMemberships.aspx', function() {
    $page = new pagebuilder;
    $page::get_template("bc");
});

$router->get("/games", function() {
    $page = new pagebuilder;
    $page::get_template("games");
});

$router->get('/newlogin', function() {
   $page = new pagebuilder;
   $page::get_template("login/newlogin");
});

$router->get("/catalog", function() {
   $page = new pagebuilder;
   $page::get_template("catalog");
});

$router->get("/Login/iFrameLogin.aspx", function() {
    $page = new pagebuilder;
    $page::get_template("login/iframelogin");
});

$router->get('/games/{id}/{urlfriendly}', function($id, $urlfriendly){
    $page = new pagebuilder;
    $page::get_template("universe", ["id"=>$id]);
});

$router->get("/upgrades/robux", function() {
    $page = new pagebuilder;
    $page::get_template("upgrades/robux");
});

$router->get("/develop", function() {
    $page = new pagebuilder;
    $page::get_template("develop");
    // develop does not work rn
});

$router->get("/games/moreresultscached", function() {
    $page = new pagebuilder;
    $page::get_template("results");
});

$router->get("/home", function() {
    $page = new pagebuilder;
    $page::get_template("new_home");
});

$router->post('/my/character.aspx', function(){
    $target = (string)($_POST['__EVENTTARGET'] ?? "");
    if (preg_match('/(Wear|Remove)[|:](\d+)$/i', $target, $m)) {
        header("Location: /my/character.aspx?action=" . strtolower($m[1]) . "&assetId=" . (string)$m[2]);
        exit;
    }
    header("Location: /my/character.aspx"); exit;
});

$router->get('/thumbnail/user-avatar', function(){ 
    $userId = isset($_GET["userId"]) ? (int)$_GET["userId"] : 0;
    if ($userId < 1) { http_response_code(400); die("Missing userid."); }
    header("Location: /Game/Tools/Avatar.ashx?userId=" . $userId, true, 302); exit;
});

$router->get("/my/account", function() {
    $page = new pagebuilder;
    $page::get_template("my/account");
});

$router->get("/my/character.aspx", function() {
    $auth = new authentication();
    $auth->requiresession();
    $user = $auth->getuserinfo($_COOKIE["_ROBLOSECURITY"] ?? null);
    if (!$user) { header("Location: /"); die(""); }
 
    global $db;
    $user = $db->table("users")->where("id", $user->id)->first();
    if (!$user) { echo "wtf"; }

    /* Handle Character Update Requests */
    if (!isset($_GET['action'])) { // No action, dont do anything just go 2 page
        $page = new pagebuilder;
        $page::get_template("avatar");
        die();
    }

    if ($_GET['action'] === 'color') { // do color things and load page
        $part = (string)($_GET['part'] ?? "");
        $colorId = isset($_GET['colorId']) ? (int)$_GET["colorId"] : 0;
        $columns = ['Head'=>'head_color', 'Torso'=>'torso_color', 'LeftArm'=>'left_arm_color', 'LeftLeg'=>'left_leg_color', 'RightArm'=>'right_arm_color', 'RightLeg'=>'right_leg_color'];
        if (!isset($columns[$part]) || $colorId < 1 || $colorId > 10000) { http_response_code(400); die("Stop messing around invalid stuff"); }

        $db->table("users")->where("id", (int)$user->id)->update([$columns[$part] => (string)$colorId]);
        $page = new pagebuilder;
        $page::get_template("avatar");
    } elseif (isset($_GET["assetId"])) { // do asset things and load page
        $assetId = (int)$_GET["assetId"];
        $action = strtolower((string)$_GET["action"]);
        $asset = $db->table("assets")->where("id", $assetId)->first();
        $owned = $db->table("ownedassets")->where('userid', (int)$user->id)->where("assetid", $assetId)->first();
        if (!$asset || !$owned || !$in_array($action, ['wear', 'remove'], true)) {
            http_response_code(400);
            die("Stop messing around invalid stuff");
        }

        $type = (int)$asset->prodcategory;
        $updates = [];
        $clothingSlots = [2=>'tshirt',11=>'shirt',12=>'pants'];
        if (isset($clothingSlots[$type])) {
            $column = $clothingSlots[$type];
            $updates[$column] = ($action === 'wear') ? $assetId : (((int)$user->$column === $assetId) ? 0 : (int)$user->$column);
        } elseif (in_array($type, [8,41,42,43,44,45,46,47], true)) {
            $hatColumns = ['hat1', 'hat2', 'hat3'];
            $existing = [];
            foreach ($hatColumns as $col) $existing[$col] = (int)($user->$col ?? 0);

            if ($action === 'remove') {
                foreach ($hatColumns as $col) $existing[$col] = (((int)$user->$column === $assetId) ? 0 : (int)$user->$column);
            } elseif (!in_array($assetId, $existing, true)) {
                $empty = null;
                foreach ($hatColumns as $col) if ($existing[$col] === 0 && $empty == null) $empty = $col;
                $updates[$empty ?? 'hat1'] = $assetId;
            }
        } else {
            $page = new pagebuilder;
            $page::get_template("avatar");
            die();
        }
        if ($updates) $db->table('users')->where('id', (int)$user->id)->update($updates);
        
        $page = new pagebuilder;
        $page::get_template("avatar");
        die();
    }
});

$router->get("/my/groups.aspx", function() {
    $page = new pagebuilder;
    $page::get_template("groups/default");
});

$router->get("/my/money.aspx", function() {
    $page = new pagebuilder;
    $page::get_template("my/money");
});

$router->get('/Thumbs/Place.aspx', function(){
    header("Location: /images/9a4a5d6f14dd9785c0af4175e7aff706.png");
});

$router->get("/userads/{num}", function($num) {
    $sanitize = new sanitize();
    $num = sanitize::integer($num);
    
    if($num > 3 || $num < 1){
        global $router;
        $router->return_status(404);
    } else {
        $page = new pagebuilder;
        $page::get_template("userads/$num");
    }
    
});

$router->get("/CSS/Base/CSS/FetchCSS", function() {
    // var_dump($_GET);
    if(!isset($_GET["path"])){
        global $router;
        $router->return_status(404);
    } else {
        $path = $_GET["path"];
    }
    
    $sanitize = new sanitize();
    $path = $sanitize::get($path);
    
    if(file_exists("../storage/css/" . $path)){
        $css = file_get_contents("../storage/css/" . $path);
        header("Content-type: text/css");
        echo $css;
        die();
        
    } else {
        global $router;
        $router->return_status(404);
    }
    
});

$router->group('/group', function($router) {
    
    $router->get("/hi", function () {
        echo "test<br>";
    });
    
}, 'checkhelp');
