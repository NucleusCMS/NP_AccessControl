NP_AccessControl
========================================

Nucleus plugin. Make blogs/items accessible only for logged in members.

Forum Thread
--------------------------
http://forum.nucleuscms.org/viewtopic.php?t=6138

Installing
--------------------------
* Unzip the file
* Upload the 'NP_AccessControl.php' and 'accesscontrol' directory to your plugin directory
* Upload the 'loginform' to your skin directory
* Install the plugin
* Install the skin
* Open blog setting from admin panel and configure protection.

How to use the plugin
--------------------------
* Blog protection. If someone tries to access the protected blog, it shows login form. If login is successful, the user can browse the blog.
* Skin restriction. It prevents unintended skin use using skinid=xx in the url.

Options
--------------------------
* Blog option 'Protect this blog': protect this blog or not. you can restrict to all loggedin users (Only to Loggedin users) or only to team members(Only to Team members). default is 'Don't protect'.
* Blog option 'Skin for login form' : skin to show when non logged in user accessed the blog. the skin must have 'index' part which has the login form, and 'error' part. default is 'loginform' which is included in this plugin.
* Blog option 'Maximum iteration for login failure' :set how many times it shows the login form. must be 1 or over. default is 1.
* Blog option 'Use skin restriction' : use skin restriction feature. it works independently from protect feature.
* Blog option 'Allowed skins(except the default skin for the blog)': allowed skins to use for the blog. default skin is automatically allowed. use comma to separate.
* Item option (3.2 only) 'protect this item': this option make it possible to use protect feature per item.

History
--------------------------
* [Version 1.0, released 2005-05-10]
* [Version 1.1, released 2005-08-29]
