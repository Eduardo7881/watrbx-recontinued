// The one-in-a-million foxy. Every click on the page rolls the dice; on a hit,
// foxy covers the screen and screams once. Purely an easter egg -- the assets
// are only fetched on a hit, so this costs everyone else a few hundred bytes
// of script and nothing else.
//
// Guard flag: asyncnav swaps can re-run page scripts, and one listener on
// document is all the site needs. Capture phase because a lot of the archived
// Roblox JS calls stopPropagation() on its click handlers, and the dice
// should still roll on those clicks.
if (!window.__wb_foxy) {
    window.__wb_foxy = true;

    // One in 5,000 normally. On Halloween (the visitor's local October 31st --
    // the browser decides, so the spooky odds arrive with *their* Halloween)
    // it's one in 100, because the site being unusable-by-jumpscare for one
    // day a year is the whole joke.
    // Deleted accounts get foxy on EVERY click: /Membership/NotApproved.aspx
    // loads this script with ?always=1, and the dice is skipped entirely.
    // Anyone else landing on NotApproved gets ?chance=5 -- one in five, which
    // reads as "the site is annoyed with you" without making the page
    // unusable. currentScript is exactly this tag, so a page carrying both a
    // NotApproved variant and the normal buildheader one lets whichever ran
    // first win -- and the guard flag below makes the second a no-op.
    var FOXY_ALWAYS = document.currentScript !== null &&
        /[?&]always=1\b/.test(document.currentScript.src || "");

    var FOXY_CHANCE = (function () {
        var src = document.currentScript !== null ? (document.currentScript.src || "") : "";
        var forced = /[?&]chance=(\d+)/.exec(src);
        if (forced) return 1 / parseInt(forced[1], 10);
        var now = new Date();
        var halloween = now.getMonth() === 9 && now.getDate() === 31;
        return halloween ? 1 / 100 : 1 / 5000;
    })();
    var FOXY_GIF = "/scare/foxy.gif";
    var FOXY_WAV = "/scare/jumpscare.wav";
    var FOXY_DURATION_MS = 5000;

    var foxyActive = false;

    function dismissFoxy(overlay, audio) {
        // Pause before hiding so the tail of the wav can't keep playing
        // after foxy is gone.
        if (!overlay.parentNode) return;
        try { audio.pause(); } catch (e) { }
        overlay.parentNode.removeChild(overlay);
        foxyActive = false;
    }

    function scare() {
        foxyActive = true;

        var overlay = document.createElement("div");
        overlay.style.cssText =
            "position:fixed;top:0;left:0;width:100%;height:100%;" +
            "background:#000;z-index:2147483647;";

        var img = document.createElement("img");
        img.src = FOXY_GIF;
        img.style.cssText =
            "position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);" +
            "max-width:100%;max-height:100%;";
        overlay.appendChild(img);

        document.body.appendChild(overlay);

        // The click that triggered this is the user gesture, so play()
        // is allowed here -- no autoplay-policy trouble.
        var audio = new Audio(FOXY_WAV);
        audio.play().catch(function () { });

        // Any click dismisses, and so does sitting through it.
        overlay.addEventListener("click", function () {
            dismissFoxy(overlay, audio);
        });
        setTimeout(function () {
            dismissFoxy(overlay, audio);
        }, FOXY_DURATION_MS);
    }

    document.addEventListener("click", function () {
        if (foxyActive) return;
        if (FOXY_ALWAYS || Math.random() < FOXY_CHANCE) {
            //scare();
        }
    }, true);

    // Phantom Freddy. Once a second, a 1-in-50 roll; on a hit he glides
    // across the screen -- silently, pointer-events off, never interrupting
    // whatever the user is doing. Most crossings take half a minute, but each
    // one separately rolls a 1-in-100 "go really fast" that turns it into a
    // 3-second blur. The gif is only fetched on a hit, so idle visitors pay
    // one timer tick a second and nothing else.
    var PHANTOM_GIF = "/scare/phantomfreddy.gif";
    var PHANTOM_CHANCE = 1 / 50;
    // Two speeds: a dignified half-minute glide, or -- one in a hundred --
    // a 3-second blur that reads less as "walking" and more as "where did
    // he go".
    var PHANTOM_TRAVEL_MS = 30000;
    var PHANTOM_SPEED_CHANCE = 1 / 100;
    var PHANTOM_FAST_TRAVEL_MS = 3000;

    var phantomActive = false;

    function phantomWalk() {
        if (phantomActive) return;
        phantomActive = true;

        var travel = Math.random() < PHANTOM_SPEED_CHANCE
            ? PHANTOM_FAST_TRAVEL_MS
            : PHANTOM_TRAVEL_MS;

        var img = document.createElement("img");
        img.src = PHANTOM_GIF;
        img.style.cssText =
            "position:fixed;bottom:0;left:-300px;height:60vh;" +
            "pointer-events:none;z-index:2147483646;" + // one below foxy's overlay
            "opacity:0.7;";

        document.body.appendChild(img);

        // requestAnimationFrame rather than a CSS transition/animation so the
        // crossing finishes wherever the tab actually is -- a CSS animation
        // pauses with the tab and picks up where it left off on return, which
        // is fine too, but rAF keeps his position and the timeout dismissal
        // in the same clock.
        var start = null;
        function step(ts) {
            if (start === null) start = ts;
            var progress = (ts - start) / travel;
            if (progress >= 1 || !img.parentNode) {
                if (img.parentNode) img.parentNode.removeChild(img);
                phantomActive = false;
                return;
            }
            img.style.left = (-300 + progress * (window.innerWidth + 600)) + "px";
            requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    setInterval(function () {
        if (!document.hidden && Math.random() < PHANTOM_CHANCE) {
            phantomWalk(); // Annoyance.
        }
    }, 1000);
}
