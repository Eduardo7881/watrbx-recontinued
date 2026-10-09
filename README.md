# WatrBX Recontinued (also known as `watrbx-fixed`)
WatrBX Recontinued is a project that recontinues the **watrbx 1** old ROBLOX revival (2015-2016)

# What we aim to:
We aim to achieve some objectives.
- Make a real 2016 client connect to this and work.
- Get the rest of the website working

# What's new?
We've ofcourse added things that the original revival didn't have, such as:
- Functional Admin Panel (yes, only thing vibe-coded, sorry for that)
- Easy way to start/stop
- A first-time setup script
- Fixes and Patches
- Container support (to store assets, thumbnails and stuff)

# Roadmap
- [ ] Character Customization
- [ ] Player Renderers (half done)
- [ ] Asset Renderers (Models)
- [ ] Functional Catalog Page (currently broken)
- [ ] Functional Develop Page (currently broken)
- [X] Admin Panel
- [X] Local Storage Patches
- [X] DataBase Fixes
- [ ] Account Settings Page (extremely broken right now)

# Disclaimer
Some of this project's asset(s) are owned by ROBLOX Corporation.
We're not affiliated with ROBLOX Corporation in any way.


I am NOT watrabi (original watrbx owner)
This isn't the source code of **watrbx 2**.
This is not a leak, it was public.

# Contribuiting
I'd be very happy if you contributed to the project, such as adding a feature, making a fix/patch or suggesting a feature.

# Running
I only ran this on Linux. You will have to figure it out on Windows/macOS tho.
    ## Linux:
        1. Install the required dependencies with this command (Ubuntu)
           `apt install composer php mariadb openssh openssl-tools`
        2. For first-time setup, run the interactive setup program on the repository root with:
           `./init.sh`
        3. If not first-time setup, and MariaDB is not running, you can run it using:
           `./start-db.sh &`
        4. Start the PHP Development Server using:
           `./server`
        5. profit

# Thing you should know:
I'm EXTREMELY new to PHP, I know some basic things but not advanced stuff. And I only used AI (a.k.a. ChatGPT) for making the Admin Panel and getting this running since watrabi did NOT explain how to run it.


Ofcourse, with this project I keep learning stuff I didn't know. You can expect some crazy code when reading this.
