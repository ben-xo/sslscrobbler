SSLScrobbler v0.8
=================

SSLScrobbler is a Scrobbler for Serato ScratchLive! (http://www.serato.com/) 
written in PHP. See http://www.last.fm/help/faq?category=99 for an explanation 
of Scrobbling.

SSLScrobbler is designed to update Last.fm and/or Twitter when a track is 
playing or played. 

SSLScrobbler could easily in future be hooked into a projector to show what's 
now playing, or used to control other actions based on track listing.

ScratchLive itself logs plays to a binary history file during playback. This app
reads the binary file and models what's going on. It sends you Growl popups when
certain events happen, such as a track is loaded on the deck, set "Now Playing" 
or can be scrobbled.

The app works on OS X (Mac) and Windows.


0. CONTENTS
=============
1. OPERATING SSLSCROBBLER
2. HOW IT WORKS
3. ADVANCED USE
4. TROUBLESHOOTING
5. FOR DEVELOPERS
6. THANKS & SHOUTS 
7. CREDITS & LICENSE


1. OPERATING SSLSCROBBLER
===========================

You should start SSLScrobbler before starting ScratchLive!, and then close it 
down after closing down ScratchLive!. If you have no idea how to start it, See 
1.2 for the 'Getting Started' guide. 

To close SSL Scrobbler, press Ctrl-C.

SSLScrobbler will read the current 'session' file from the ScratchLive! history 
folder. That means that if you click 'End Session' in the ScratchLive! history 
pane, you will have to restart SSLScrobbler.

If you have not installed ScratchLive! into the default location, you will have 
to provide the full path to the current session file to SSLScrobbler. See 
section 4, TROUBLESHOOTING, for more information.

1.1 Installation
-------------------

* OSX (Mac):
  
  SSLScrobbler needs no particular installation, although if it's not already, 
  you might like to install Growl. (In my experience, most Mac users already 
  have Growl).
  
  SSLScrobbler is best started from the Terminal.
  
* Windows:

  You should install PHP 5.3 and Growl. You must reboot after installing these, 
  even if it doesn't ask!
  
  You can download and install PHP 5.3 from http://windows.php.net/download/
  (You probably want the 'Installer' nearest the top of the page, unless you
  know better.)
  
  I strongly suggest you make the following change to your php.ini file (which 
  can usually be found in C:\Program Files\PHP). Open the file in Notepad, and 
  then find the line which says...
  
    display_errors = 
    
  ...and change it to On if it is Off.  
  
  SSLScrobbler is best started from a DOS box.


1.2 Getting Started
----------------------

SSLScrobbler is currently designed to be run from the command line. For 
convenience only, there are shortcuts provided to double-click for Mac and 
Windows. But best results can be obtained by running from Terminal (Mac) or a 
DOS box (Win).

Very very quick start:

 * OSX (Mac): double click "historyreader-mac"
 * Windows:   double click "historyreader-win"
 
 To quit SSL Scrobbler, click on its window and press Ctrl-C. 
 
Better start, from Terminal:

 OSX (Mac):
 
 * Open Terminal (you can open it quickly from Spotlight)
 * Drag the file historyreader.php into the Terminal window, and hit enter. It 
   should say something like: 
    
   $ /Users/ben/Downloads/sslscrobbler/historyreader.php 
    
 * For help and information on options, type --help before hitting enter. e.g: 

   $ ./historyreader.php --help

   To quit SSL Scrobbler, click on its window and press Ctrl-C.
    
 Windows:
 
 * Open a DOS box. You can do this by clicking 'Start' -> Run -> typing "cmd" 
   and pressing enter. 
 * Type 'php' and then drag the file historyreader.php into the DOS box, and hit 
   enter. It should say something like:
    
   C:\> php "C:\Documents and Settings\ben\Desktop\historyreader.php"
    
 * For help and information on options, type --help before hitting enter. e.g.:
 
   C:\> php "C:\Documents and Settings\ben\Desktop\historyreader.php" --help
 
   To quit SSL Scrobbler, click on its window and press Ctrl-C.
    
 
1.3 Options
--------------

Add the following options to the command when running from Terminal / DOS: 

 -h or --help
  A reminder of this information.

 -i or --immediate
  Do not wait for the next history file to be created, but use the most recent 
  one.
   
  You must use this option if you started SSLScrobbler mid-way through a 
  session, or if you had to restart SSLScrobbler for some reason.
   
  This option is ignored if you specify the full path to a specific history 
  file.
   
 -v or --verbosity <0-9>:   
  Increase the amount of information shown in the console. If you really want to
  see a lot about what's going on, try -v 9 . 
   
  You should try -v 9 and save the output if you are having problems, before 
  reporting a bug to me, or contacting me for advice...
   
 -l or --log-file <file>:   
  Write the output to a file. (If this option is omitted, output goes to the 
  screen)
 
Last.fm options:
 -L or --lastfm <username>: 
  Scrobble / send 'Now Playing' to Last.fm for user <username>. 
  
  The first time you specify this, it will ask you to authorize the app to your 
  Last.fm account. The authorization information is stored in a file called 
  <username>-lastfm.txt
  
Twitter options:
 -T or --twitter <session>:
  Post tracklists to Twitter. It will tweet once for every 'Now Playing'. 
  
  The first time you specify this option, it will ask you to authorize the app 
  to your Twitter account. The authorization information is stored in a file 
  called <session>-twitter.txt

2. HOW IT WORKS
=================

SSLScrobbler monitors the current ScratchLive! history file. The history file is
a binary file containing information about all the tracks in the session. 
ScratchLive! updates this file every time you add a track to a deck or eject a 
track from a deck (and in a few other situations). The history file actually 
contains a lot of information - everything you see in the history pane, and then
some. ScratchLive never removes or rewrites information in this file while 
you're performing, so it may append several chunks of information referring to 
the same track. (Later, when you shut ScratchLive! down, it compacts the file to 
remove duplicate information).

However, SSLScrobbler does not have access to the actual play time or play 
position of the songs, so it has to guess this.


3. ADVANCED USE
=================

* If you want to change the message sent to Twitter, edit historyreader.php.

* If you're interested in exploring the ScratchLive! binary file format, check 
  out the --dump option. You can even use this to dump non-history files (such 
  as the file 'database v2'). 
 
 
4. TROUBLESHOOTING
====================

* SSLScrobbler looks for history files in the default locations, which are:
  
  Mac:
  * $HOME/Music/ScratchLIVE/History/Sessions
  
    ($HOME is usually e.g. /Users/<username>)
  
  Windows Vista / Windows 7:
  * %USERPROFILE%\Music\ScratchLIVE\History\Sessions

    (%USERPROFILE% is usually e.g. C:\Users\<username>)
    
  Windows XP:
  * %USERPROFILE%\My Documents\My Music\ScratchLive\History\Sessions
  
    (%USERPROFILE% is usually e.g. C:\Documents and Settings\<username>)
  
* make sure display_errors = On in your php.ini if you want more useful help, 
  and before reporting bugs.
  
* If the internet is down or drops out, Scrobbling to Last.fm may make the whole
  app freeze until the scrobbling times out. This means that updates to Now 
  Playing will not appear during this period, although no scrobbles will be 
  lost.
  
* If you find Scrobbling, Tweeting etc to be particularly slowing down the app 
  (that is causing delays when Now Playing is not updated), try installing the 
  PCNTL extension to PHP. (Without the PCNTL extension, the app will be 
  single-threaded).
  
 
5. FOR DEVELOPERS
===================

5.1 Unit Tests
-----------------

Run with phpunit:
 * phpunit --bootstrap Tests/bootstrap.php Tests
 
5.2 Plugins
-----------------

It's quite easy to write plugins for SSLScrobbler. Examine the examples in the 
folder SSL/Plugins. 

A plugin is a class which implements SSLPlugin, and can provide zero-or-more 
Observers for the different events (zero would be quite uninteresting, though) 
from the getObservers() method.

The following observer types are currently provided:
* TickObserver - triggered by a timer interrupt (usually every 2 seconds).
* SSLDiffObserver - notified of new changes to the ScratchLive! history file.
* TrackChangeObserver - triggered when a track is loaded or removed from a deck.
* NowPlayingObserver - triggered when a track becomes the 'Now Playing' track
* ScrobbleObserver - triggered when a track is definitively scrobble-able. 


6. THANKS & SHOUTS
====================

Thanks:
* Jesse Ward (jw76), for beta testing and bug reports

Shouts:
* Last.fm for letting me use the back room of the office to broadcast loud radio
  shows. Oh, and for employing me. Thanks!
* Donnovan, Louis and Daniel at Bassdrive (bassdrive.com)
* Mike Louth at Digitally Imported (di.fm)


7. CREDITS & LICENSE
======================

SSLScrobbler is Free Open Source Software by Ben XO.
SSLScrobbler is licensed under the MIT license.

SSLScrobbler includes the following external libraries:

* php-growl (http://github.com/tylerhall/php-growl/)
  originally by Tyler Hall, licensed under the MIT license.

* PHP-Scrobbler (http://github.com/ben-xo/PHP-Scrobbler/)
  originally by Mickael Desfrenes, licensed under the LGPL license.
  
* PHP Lastfm API (http://www.matto1990.com/projects/phplastfmapi/)
  originally by Matt Oakes, licensed under the MIT license.
  
* Twitter OAuth (http://github.com/abraham/twitteroauth/)
  originally by Abraham Williams, licensed under the MIT license.
  
* PHP-Twitter (http://code.google.com/p/php-twitter/)
  originally by Tijs Verkoyen, licensed under the BSD license.
  
* getID3 (http://getid3.sourceforge.net/)
  originally by James Heinrich, licensed under the GPL license.
  
* vgd.php (http://v.gd/developers.php)
  originally by Richard West, released into the public domain.

The "NowPlaying" plugin, written for Boston Music Hack Day 
(http://musichackdayboston.pbworks.com/w/page/31299401/sQRatchLive) also 
comes bundled with the following:

* 7Digital PHP Wrapper (https://github.com/gregsochanik/api_php_example)
  originally by Greg Sochanik, licensed under XXXXXXXX
  (Includes JSON.php by Michal Migurski, licensed under the BSD license.)
  
* JQuery (http://jquery.com/)
  originally by John Resig, licensed under the MIT license.
