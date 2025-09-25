# SSLScrobbler v0.32

[![Testing sslscrobbler](https://github.com/ben-xo/sslscrobbler/actions/workflows/testing.yml/badge.svg)](https://github.com/ben-xo/sslscrobbler/actions/workflows/testing.yml)

SSLScrobbler is a Scrobbler for Serato DJ and Serato ScratchLive (http://www.serato.com/) 
written in PHP. See https://www.last.fm/about/trackmymusic for an explanation 
of Scrobbling.

SSLScrobbler is designed to update Last.fm, Twitter and/or Discord when a track
is playing or played. 

It is so named because Serato DJ used to be called Serato Scratch Live (SSL)
when I started this project.

SSLScrobbler can easily be customed to, for example, show what's currently
playing on a projector, or send information to OBS (Open Broadcast Studio), or
used to control other actions based on track listing.

Serato DJ itself logs plays to a binary history file during playback. This app
reads the binary file and models what's going on.

The app works on OS X (Mac) and Windows.


# -1. A NOTE ON REPORTING BUGS

It may not do everything you want, and I certainly haven't tried every function of Serato to see what happens.

If you encounter bugs 🐛 🐜 🐞, please copy-paste the entire output from the program into a new ticket for me
on my GitHub, along with a description of what you expected to happen as well as what actually happened 👍

https://github.com/ben-xo/sslscrobbler/issues


# 0. CONTENTS

1. OPERATING SSLSCROBBLER
  * Installation
  * Getting Started
  * Quick HOWTO
  * Options
2. HOW IT WORKS
3. ADVANCED USE
4. TROUBLESHOOTING
5. FOR DEVELOPERS
  * Plugins
  * Unit Tests
  * Architecture
6. THANKS & SHOUTS
7. FAQ
8. CREDITS & LICENSE


# 1. OPERATING SSLSCROBBLER

You should start SSLScrobbler before starting Serato DJ!, and then close it 
down after closing down Serato DJ. If you have no idea how to start it, See 
1.2 for the 'Getting Started' guide. 

To close SSL Scrobbler, press Ctrl-C.

SSLScrobbler will read the current 'session' file from the Serato DJ history 
folder. It will follow from session to session, but use the `-i` option if you
already have a session open.


## 1.1 Installation


### 1.1.1 macOS
    
  There is an app you can download from https://github.com/ben-xo/sslscrobbler/releases

  SSLScrobbler needs no particular installation, although if it's not already, 
  you might like to install `terminal-notifier` ("`brew install terminal-notifier`" -
  If you don't have the `brew` command, install Mac Homebrew from https://brew.sh/ )

  SSLScrobbler can also be started from Terminal, and has more options this way.

  To start from Terminal you will need PHP installed (recommended version 8.4).
  You can install PHP through Mac Homebrew https://brew.sh/. Once installed,
  
        brew install php@8.4
        # (or just brew install php)

  See "Getting Started" for more.


### 1.1.2 Windows
  
  I haven't tested this part in a long time. I have never owned a Windows 10 or
  Windows 11 machine as a primary machine. I welcome any feedback.
  
  Try installing PHP by following the instructions on https://www.php.net/
   
  You should install PHP 8 and Growl. You must reboot after installing these, 
  even if it doesn't ask!
  
  If you're on Windows XP, 32-bit Vista or 32-bit Windows 7, you can download 
  and install PHP from https://windows.php.net/download/
  (You probably want the 'Installer' nearest the top of the page, unless you
  know better.)
   
  I strongly suggest you make the following change to your `php.ini` file (which 
  can usually be found in `C:\Program Files\PHP`). Open the file in Notepad, and 
  then find the line which says...
  
        display_errors = 
    
  ...and change it to `On` if it is `Off`.  
  
  SSLScrobbler is best started from a DOS box / Command prompt (see below)


## 1.2 Getting Started


SSLScrobbler is designed to be run from the command prompt / terminal, but on 
macOS there is also a GUI to help you get started.


### 1.2.1 TO START

I use this app through Terminal on macOS. But, there is a macOS GUI version too.
On Windows you must use Command Prompt. There is a guided-prompt setup mode.
Read on.

#### 1.2.1.1 macOS (super-easy method):
 
The simplest way to get started is to use the macOS app. 
 * Download the macOS.zip file from https://github.com/ben-xo/sslscrobbler/releases
 * unzip it to get `SSL Scrobbler.app` (with a nice vinyl record icon)
 * Option-click it and choose "Open", then confirm you are sure that it's okay
 * The app will ask you for permission to use `SystemUIServer`. Say yes. This is 
   so we can pop up questions.
 * It will then pop up some questions. Just follow the prompts.
 * Then start Serato DJ and watch what happens!


#### 1.2.1.2 macOS (traditional method / more options):

It is more flexible when used from Terminal.

 * Open Terminal (you can open it quickly from Spotlight)
 * Drag the file historyreader.php into the Terminal window, and hit enter. It 
   should say something like: 
    
        $ /Users/ben/Downloads/sslscrobbler/historyreader.php 
    
 * For help and information on options, type `--help` before hitting enter. e.g: 

        $ ./historyreader.php --help

 * For the guided setup mode, try `--prompt` .


#### 1.2.1.3 Windows:

 There's no GUI version for Windows yet. Sorry.
 
 * Open a Command Prompt. You can do this by clicking 'Start' -> Run -> typing "`cmd`" 
   and pressing enter. 
 * Type `php` and then drag the file `historyreader.php` into the command prompt, and hit 
   enter. It should say something like:
    
        C:\> php "C:\Documents and Settings\ben\Desktop\historyreader.php"
    
 * For help and information on options, type `--help` before hitting enter. e.g.:
 
        C:\> php "C:\Documents and Settings\ben\Desktop\historyreader.php" --help
 
 * For guided setup mopde, try `--prompt` .


### 1.2.2 TO QUIT

To quit SSL Scrobbler, click on its window and press `Ctrl`+`C`.
(or in the macOS app, press the quit button in the bottom right)


## 1.3 Quick HOWTO


TO SCROBBLE AS YOU PLAY:

    php historyreader.php -L lastfmusername


TO SCROBBLE THE PREVIOUS SET (e.g. from your gig last night):

    php historyreader.php -L lastfmusername --post-process


TO SCROBBLE SEVERAL PEOPLE IN THE ROOM:

    php historyreader.php -L lastfmusername -L lastfmusername2 -L lastfmusername3


TO TWEET AS YOU PLAY

    php historyreader.php -T twitterusername


TO MESSAGE DISCORD AS YOU PLAY

    php historyreader.php --discord webhook-name


MAKE TRACK DATA AVAILABLE FOR OBS (OR WHATEVER)

    php historyreader.php -J 8080


MAKE TRACK DATA FOR BUTT (OR WHATEVER)

    php historyreader.php -ln nowplaying.txt


## 1.4 Options

Add the following options to the command when running from Terminal / DOS to
control the behaviour.

If you supply no options, SSLScrobbler will show you that it's working, but the
information won't be sent anywhere or available to use except by reading the
output; so you will want to study the options (especially the options from
`--plugin-help`)

### 1.4.1 Basic options


`-h` or `--help`
A reminder of this information.

`--prompt`
Guided setup mode.

You will be asked a series of yes or no questions. (Not all options are available
in this mode).

`-l` or `--log-file <file>`:
Write the output to a file. (If this option is omitted, output goes to the 
screen)

`-i` or `--immediate`
Do not wait for the next history file to be created, but use the most recent 
one.
   
You must use this option if you started SSLScrobbler mid-way through a 
session, or if you had to restart SSLScrobbler for some reason.
 
This option is ignored if you specify the full path to a specific history 
file.
   
`-v` or `--verbosity <0-9>`:
Increase the amount of information shown in the console. If you really want to
see a lot about what's going on, try `-v 9` . 
 
You should try `-v 9` and save the output if you are having problems, before 
reporting a bug to me, or contacting me for advice...
 

`-p` or `--post-process`
Immediately processes everything in the last history file. Ideal for
scrobbling that set you played last night.
 
**Now Playing text file options**
All of these options output the current playing track to a file, but in different
formats.

`-ln` or `--log-track <file>`:
log the current playing track to a file (e.g. for streaming)

The file will contain the text `Artist Name - Song Title` when a song is playing,
and the file will be empty the rest of the time.

`-ls` or `--log-serialized <file>`:
log the current playing track to a file in PHP serialized form. This contains more
info, but is not human readable - it's useful if you want to build other PHP
scripts for a web server.

`-lt` or `--log-tostring <file>`: log the current playing track to a file in a
fuller representation, like what comes out in the console log, e.g.:

    PLAYED:1 - ADDED:1 - DECK:1 - Artist - Title - 0:0

Most people want `-ln`


**Last.fm options**:
`-L` or `--lastfm <username>`: 
Scrobble / send 'Now Playing' to Last.fm for user <username>. 

The first time you specify this, it will ask you to authorize the app to your 
Last.fm account. The authorization information is stored in a file called 
`lastfm-<username>.txt`

NOTE: you can include `-L` multiple times and scrobble to multiple accounts.
  
**Twitter options**:
`-T` or `--twitter <session>`:
Post tracklists to Twitter. It will tweet once for every 'Now Playing'. 

The first time you specify this option, it will ask you to authorize the app 
to your Twitter account. The authorization information is stored in a file 
called `twitter-<session>.txt`

NOTE: you can include `-T` multiple times and tweet to multiple accounts.

**Discord options**:
`--discord <session>`:
Post tracklists to Discord. It will send a message once for every 'Now Playing'. 

The first time you specify this option, it will ask you to paste a URL for a webhook
which can be provided by your guild admin for a channel on your server. The 
authorization information is stored in a file called `discord-<session>.txt`

NOTE: you can include `--discord` multiple times and message multiple channels.
  
**DB options**:
`-D` or `--db <key>`:
Put the now playing track into a database row. It will issue one SQL 
statement for every 'Now Playing'.

Exactly what SQL is run, and where it is sent, is configued in `config.php`.
  
**IRCCat options**:
`-I` or `--irccat host:port#channel`
Put the now playing track into an IRC channel using IRCCat. (IRCCat sold
separately - https://github.com/RJ/irccat).   


**JSON Server options**
`-J` or `--json port`
Makes the current playing track info available at `http://<your ip>:<port>/nowplaying.json`.
Also, makes it available in a way which can be styled using CSS (which e.g. for OBS) at 
`http://<your ip>:<port>/nowplaying.html`


# 2. HOW IT WORKS


SSLScrobbler monitors the current Serato DJ history file. The history file is
a binary file containing information about all the tracks in the session. 
Serato DJ updates this file every time you add a track to a deck or eject a 
track from a deck (and in a few other situations). The history file actually 
contains a lot of information - everything you see in the history pane, and
then some. ScratchLive never removes or rewrites information in this file while
you're performing, so it may append several chunks of information referring to 
the same track. (Later, when you shut Serato DJ down, it compacts the file to 
remove duplicate information).

However, SSLScrobbler does not have access to the actual play time or play 
position of the songs, so it has to guess this. See the FAQ section for more
info about the heuristic used for this "best guess".


# 3. ADVANCED USE


* If you want to enable or disable plugins, or change API keys, or other
  advanced "configuration", copy `config.php-default` to `config.php` and edit.
  You can also change e.g. the Twitter template here.

* If you're interested in exploring the Serato DJ binary file format, check 
  out the `--dump` option. You can even use this to dump non-history files
  (such as the file `database v2`). 
 
 
# 4. TROUBLESHOOTING


* SSLScrobbler looks for history files in the default locations, which are:
  
  Mac:
  * `$HOME/Music/_Serato_/History/Sessions`
  
    (`$HOME` is usually e.g. `/Users/<username>`)
  
  Windows Vista / Windows 7 / Windows 10:
  * `%USERPROFILE%\Music\_Serato_\History\Sessions`

    (`%USERPROFILE%` is usually e.g. `C:\Users\<username`)
    
  Windows XP:
  * `%USERPROFILE%\My Documents\My Music\_Serato_\History\Sessions`
  
    (`%USERPROFILE%` is usually e.g. `C:\Documents and Settings\<username>`)
  
* make sure `display_errors = On` in your `php.ini` if you want more useful help, 
  and before reporting bugs.
  
* If the internet is down or drops out, posting to any of the services (e.g.
  Last FM, Twitter, Discord, etc) may make the whole app freeze until the
  attempt times out. This means that updates to Now Playing will not appear 
  during this period, and the message may not appear on the service (although 
  in the case of the Last FM plugin, no scrobbles will be lost, they'll be
  submitted later).
  
* If you find Scrobbling, Tweeting etc to be particularly slowing down the app 
  (that is causing delays when Now Playing is not updated), try installing the 
  `PCNTL` extension to PHP. (Without the PCNTL extension, the app will be 
  single-threaded). However, I've found it doesn't really make much difference.
  
 
# 5. FOR DEVELOPERS

 
## 5.1 Plugins


It's quite easy to write plugins for SSLScrobbler. Examine the examples in the 
folder `SSL/Plugins`.

All plugins can be can be activated (or deactivated) by adding them into
`config.php` (with a sensible default set provided in `config.php-default`).
Most of them have options you can set too.

Most plugins can be configured more than once, for example to tweet to two
accounts.

Any class which implements `SSLPlugin` can be configured as a plugin,
and can provide zero-or-more Observers for the different events from
the `getObservers()` method. The Observers are for reacting to the various
signals which are generated throughout the session.

The following observer types are currently provided:
* `TickObserver` - triggered by a timer interrupt (usually every 2 seconds).
* `SSLDiffObserver` - notified of new changes to the Serato DJ history file.
* `TrackChangeObserver` - triggered when a track is loaded or removed from a deck.
* `NowPlayingObserver` - triggered when a track becomes the 'Now Playing' track
* `ScrobbleObserver` - triggered when a track is definitively scrobble-able. 

Many plugins can also be configured through command line options. If a plugin
which also implements the interface `CLIPlugin` is configured in `config.php`,
it will present its options in `--plugin-help`. Most of the plugins useful
for DJing work this way.

`CLIPlugin`s may also provide prompts for the `--guided` setup mode.

I choose to write my CLI plugin counterparts as separate plugins in their own
right (plugins for adding more plugins, basically), and you can see that it's
mostly the CLI versions which are in the default config. All you need to do is
implement the right interfaces, however. You don't have to copy my style
if you don't want to (but it's probably easiest if you do).

## 5.2 Unit Tests


Run with phpunit:
 * `phpunit --bootstrap Tests/bootstrap.php Tests`

## 5.3 Architecture


### 5.3.1 Runtime Model


While running, the `SSLScrobbler` engine is event driven (see 5.1 for the list
of events). Here are the main object collaborations and the ways they 
communicate. The interactions happen in serial, in the order they are numbered.

The following diagram shows how the running app is strung together. 
`HistoryReader` sets these objects up in its `monitor()` method, then asks
the `TickSource` to start ticking. Every tick, the following happens:

1. The `TickSource` sends ticks (every 2 seconds or so) to 
   `SSLHistoryFileMonitor`, which attempts to read from the current
   history file. 
2. If there is new info available in the file, the `SSLHistoryFileMonitor`
   sends a diff event (in the form of an `SSLHistoryDiffDom`, which in turn
   contains `SSLTracks`) to the `SSLRealtimeModel`.
3. The `SSLRealtimeModel` models what Serato is doing - i.e. which tracks
   are currently on each deck. It inspects the `SSLHistoryDiffDom`s that it
   receives to work out if a new track has been started, or a track has been
   stopped.
4. If a track has changed (started or stopped), the `SSLRealtimeModel` then
   notifies the `NowPlayingModel`, `ScrobbleModel` and `RealtimeModelPrinter`.
5. The `RealtimeModelPrinter` prints this info to the console.
6. The `NowPlayingModel` takes info on track changes to work out which track
   has been on the deck long enough to be considered "Now Playing".
7. The `ScrobbleModel` takes info on track changes to work out which tracks
   can be scrobbled.
8. Whenever the "Now Playing" track changes (or track play stops entirely),
   the `NowPlayingModel` sends events - mostly to plugins such as the 
   Twitter plugin, Scrobbler, and Growl or Terminal notifier.
9. Likewise, when a track becomes Scrobbleable, the `ScrobbleModel` sends 
   events to the Twitter, Scrobble and Growl plugins, etc.
10. The various plugins then do their bits such as posting to Twitter.

Here's the diagram:

    +------------+
    | TickSource |  
    +-----+------+
          |
          | A timer event (roughly every 2 seconds)
          |
          |-----------------------+-----------------------------------------+
          v 2                     v 1                                       |
    +-----------------------+ +---------------+                             |
    | SSLHistoryFileMonitor | | PluginManager |                             |
    +-----+-----------------+ +---------------+                             |
          |                                                                 |
          | Diff event (when history file changes) as an                    |
          | <SSLHistoryDiffDom> object (which contains <SSLTrack>s)         |
          |                                                                 |
          v 3                                                               |
    +------------------+  6. <TrackChangeEventList> from deck models sent   |
    | SSLRealtimeModel +----------------------------------------------+     |
    +------------------+                                              |     |
          ^                                                           |     |
          | 4. Sent: Diff event (delegated to correct deck model)     |     |
          | 5. Received: <TrackChangeEvent>s (start, stop, update)    |     |
          |                                                           |     |
          |---------------+------------ . . . --------------+         |     |
          v               v    decks created as necessary   v         |     |
    +---------------+ +---------------+       +---------------+       |     |
    | DeckModel (0) | | DeckModel (1) | . . . | DeckModel (n) |       |     |
    +---------------+ +---------------+       +---------------+       |     |
                                                                      |     |
                                                                      |     |
       +----+--------------------------+-------------------------+----+     |
       |    | Print track changes      | Decide if a stopped     |       +--+
       |    | to console               | track should scrobble   |       |
       |    v 7                        v 12                      v 8     v 16
       |  +----------------------+   +---------------+    +-----------------+ 
       |  | RealtimeModelPrinter |   | ScrobbleModel |    | NowPlayingModel |
       |  +----------------------+   +-+-------------+    +--------------+--+
       |                               |                                 |
       |                               | "Scrobble"        "Now Playing" |   
       |                               |  event                   event  +--+
       |                               |                                    |
       |                               .                                    .
       |                               . . . . Other Plugins  . . . . . . . .
       |                               .                                    .
       |                               |                                    |
       |                               |                                    |
       |                               | 14  +---------------------+  11,19 |
       |                               +---->| SSLScrobblerAdaptor |<-------+ 
       |                               |     +---------------------+        |
       |                               |                                    |
       |                               |     +---------------------+  10,18 |
       |                               |     | SSLTwitterAdaptor   |<-------+
       |                               |     +---------------------+        |
       |                               |                                    |
       |                               | 13  +---------------------+   9,17 |
       |                               +---->| SSLGrowlRenderer    |<-------+
       |                                     +---------------------+
       | Print track changes via Growl         ^ 15
       +---------------------------------------+
   
   
Various things have been omitted from this diagram, in particular the 
details of how the `PluginManager` works. The `PluginManager` is capable of
activating, deactivating and reconfiguring plugins in the event chain
at run time, and is used for configuration on-the-fly. It does this
by inserting a layer between each of the observers which keeps track of the
various event observers.

### 5.3.2 ScratchLive File Format Model


ScratchLive stores most of its data in a chunked format, where a chunk header 
is 8-bytes (4-byte identifier and a 4-byte length) followed by <length> bytes. 
Chunks themselves can contain other chunks. Within these sub-chunks are 
fields, starting with a 4-byte field ID. The meaning of the fields depends on
the chunk type. Some fields contain fixed-length data, others contain a 4-byte
length and then that many bytes of variable-length data.

Whilst exploring the file format, I invented an unpacking language called
XOUP (short for "XO's UnPacker"). XOUP is interpretted with XoupInterpreter or
compiled into Unpacker classes with XoupCompiler (all this happens 
automatically). See the comments in XoupInterpreter for info on XOUP.

ScratchLiveScrobbler, so far, recognises 7 chunk types. Some of these are 
"compound chunks" (that is, they contain other chunks), and others contain data 
("struct chunks"):

Compound Chunks:
* `OENT` - Session files have these, each containing a single `ADAT` for a track
* `OREN` - Session files have these, each containing a single `UEN` for a deletion
* `OSES` - The session index has these, each with a single `ADAT` for a session
* `OCOL` - The session index file has these each with `UCOK` and `UCOW` sub-chunks.

Struct Chunks:
* `VRSN`
  - Header chunk, contains a file format string. Occurs in all files
  - Parsed by `SSLVrsnChunk` into an `SSLVersion` object using `SSLVersionVrsn.xoup`
  
* `ADAT` - two of these, `OENT ADAT` and `OSES ADAT`
  - Data chunk, contains fields. Fields meaning file format dependent
  1. `OENT` version, parsed by `SSLAdatChunk` into an `SSLTrack` object 
    using `SSLTrackAdat.xoup`
  2. `OSES` version, parsed by `SSLAdatChunk` into an `SSLSession` object 
    using `SSLSessionAdat.xoup`
    
* `UENT`
  - Event chunk, seems to contain just an identifier referring to an `OENT ADAT`.
  - Parsed by `SSLUentChunk` into an `SSLTrackDelete` object 
    using `SSLTrackDeleteUent.xoup`
  - These occur transiently in session files when an entry is deleted from the
    playlist. Serato DJ seems to resolve these and rewrite the history file
    at shut-down time.
    
* `UCOK` and `UCOW`
  - I believe these represent column ordering and column width in the 
    Serato DJ history pane.
  - I have not written parsers for these yet.

Unknown chunk types are safely ignored (modelled by `SSLUnknownChunk` - in
`--dump` mode, these will provide a pretty hexdump to aid with implementation). 

The Serato DJ crate file (`database v2`) is also in this format, but I have 
not modelled any of it. Have fun exploring these files using `--dump` :)

### 5.3.3 Example content

Here's an example of what `--dump` might output on a history file:

    CHUNK<vrsn>: 
    	version => 1.0/Serato Scratch LIVE Review

    CHUNK<oent>: 
    		CHUNK<adat>: 
    			row => 3137
    			fullpath => /Users/ben/04 - )E!3( - Bad Company - Grunge 2.mp3
    			location => /Users/ben
    			filename => 04 - )E!3( - Bad Company - Grunge 2.mp3
    			title => Grunge 2
    			artist => )E|3( - Bad Company
    			album => Book Of The Bad (CD2)
    			genre => Drum & Bass
    			length => 06:25.31
    			bitrate => 320.0kbps
    			comments => Track 4
    			lang => eng
    			year => 2001
    			starttime => 1272398586
    			endtime => 1272398677
    			deck => 2
    			playtime => 91
    			sessionId => 3135
    			played => 0
    			added => 0
    			updatedAt => 1272398677

The same data, without the `XOUP` parser, would have printed this:

    CHUNK<oent>: 
    		CHUNK<adat>: 
    			0000 0001 0000 0004 0000 0c41 0000 0002 0000 00dc 002f 0055 0073 0065 0072 0073 ...1...4..CA...2...!./.U.s.e.r.s
    			002f 0062 0065 006e 002f 0044 006f 0077 006e 006c 006f 0061 0064 0073 002f 0042 ./.b.e.n./.D.o.w.n.l.o.a.d.s./.B
    			0043 0020 0052 0065 0063 006f 0072 0064 0069 006e 0067 0073 002f 0042 0043 0052 .C. .R.e.c.o.r.d.i.n.g.s./.B.C.R
    			0055 004b 0045 0050 0043 0044 0030 0030 0031 0020 002d 0020 0042 006f 006f 006b .U.K.E.P.C.D.0.0.1. .-. .B.o.o.k
    			0020 006f 0066 0020 0054 0068 0065 0020 0042 0061 0064 002f 0043 0044 0032 002f . .o.f. .T.h.e. .B.a.d./.C.D.2./
    			0030 0034 0020 002d 0020 0029 0045 0021 0033 0028 0020 002d 0020 0042 0061 0064 .0.4. .-. .).E.!.3.(. .-. .B.a.d
    			0020 0043 006f 006d 0070 0061 006e 0079 0020 002d 0020 0047 0072 0075 006e 0067 . .C.o.m.p.a.n.y. .-. .G.r.u.n.g
    			0065 0020 0032 002e 006d 0070 0033 0000 0000 0003 0000 008c 002f 0055 0073 0065 .e. .2...m.p.3.....3...!./.U.s.e
    			0072 0073 002f 0062 0065 006e 002f 0044 006f 0077 006e 006c 006f 0061 0064 0073 .r.s./.b.e.n./.D.o.w.n.l.o.a.d.s
    			002f 0042 0043 0020 0052 0065 0063 006f 0072 0064 0069 006e 0067 0073 002f 0042 ./.B.C. .R.e.c.o.r.d.i.n.g.s./.B
    			0043 0052 0055 004b 0045 0050 0043 0044 0030 0030 0031 0020 002d 0020 0042 006f .C.R.U.K.E.P.C.D.0.0.1. .-. .B.o
    			006f 006b 0020 006f 0066 0020 0054 0068 0065 0020 0042 0061 0064 002f 0043 0044 .o.k. .o.f. .T.h.e. .B.a.d./.C.D
    			0032 0000 0000 0004 0000 0050 0030 0034 0020 002d 0020 0029 0045 0021 0033 0028 .2.....4...P.0.4. .-. .).E.!.3.(
    			0020 002d 0020 0042 0061 0064 0020 0043 006f 006d 0070 0061 006e 0079 0020 002d . .-. .B.a.d. .C.o.m.p.a.n.y. .-
    			0020 0047 0072 0075 006e 0067 0065 0020 0032 002e 006d 0070 0033 0000 0000 0006 . .G.r.u.n.g.e. .2...m.p.3.....6
    			0000 0012 0047 0072 0075 006e 0067 0065 0020 0032 0000 0000 0007 0000 0028 0029 ...I.G.r.u.n.g.e. .2.....7...(.)
    			0045 007c 0033 0028 0020 002d 0020 0042 0061 0064 0020 0043 006f 006d 0070 0061 .E.|.3.(. .-. .B.a.d. .C.o.m.p.a
    			006e 0079 0000 0000 0008 0000 002c 0042 006f 006f 006b 0020 004f 0066 0020 0054 .n.y.....8...,.B.o.o.k. .O.f. .T
    			0068 0065 0020 0042 0061 0064 0020 0028 0043 0044 0032 0029 0000 0000 0009 0000 .h.e. .B.a.d. .(.C.D.2.).....9..
    			0018 0044 0072 0075 006d 0020 0026 0020 0042 0061 0073 0073 0000 0000 000a 0000 .O.D.r.u.m. .&. .B.a.s.s.....A..
    			0012 0030 0036 003a 0032 0035 002e 0033 0031 0000 0000 000d 0000 0014 0033 0032 .I.0.6.:.2.5...3.1.....D...K.3.2
    			0030 002e 0030 006b 0062 0070 0073 0000 0000 0011 0000 0010 0054 0072 0061 0063 .0...0.k.b.p.s.....H...G.T.r.a.c
    			006b 0020 0034 0000 0000 0012 0000 0004 656e 6700 0000 0017 0000 000a 0032 0030 .k. .4.....I...4eng....N...A.2.0
    			0030 0031 0000 0000 001c 0000 0004 4bd7 42fa 0000 001d 0000 0004 4bd7 4355 0000 .0.1.....S...4K!B!...T...4K!CU..
    			001f 0000 0004 0000 0002 0000 0021 0000 0001 0000 0000 2d00 0000 0400 0000 5b00 .V...4...2...!...1....-...4...[.
    			0000 3000 0000 0400 000c 3f00 0000 3200 0000 0100 0000 0034 0000 0001 0000 0000 ..0...4..C?...2...1....4...1....
    			3500 0000 044b d743 55                                                          5...4K!CU

All of the field names in the properly parsed output were worked out with 
educated guess-work. (The list of fields shown for the track in the example is
representative, but not all possible fields are saved with each row in the
history file. Other possible fields include bpm, album artist, etc). 

Here's how the software side of it is strung together.

The `SSLHistoryFileDiffMonitor` is responsible for monitoring the file by continually
reading it to see what's been appended. Once it sees new data, it will parse the data
into chunks and give those raw chunks to an `SSLHistoryDom` to look after. This first
phase looks like this:

    SSLHistoryFileDiffMonitor (keeps reading a History file to look for more chunks)
      | read()
      v
      SSLParser <new SSLHistoryDom $tree> (a parser that reads the file into the chosen DOM)
        | parse(<filename>)
        | readChunks()
        v
        SSLChunkReader (reads all chunks from a file and parses them)
          | $dom->addChunks( getChunks() ) 
          v
          SSLChunkParser (reads a specific chunk from the file and constructs concrete chunks)
            | readChunk() // loop
            | ->parseFromFile()
            v
            SSLChunkFactory (creates concrete chunk instances from the chunk headers)
              | newChunk()
              v
              SSLChunk <SSLVrsnChunk / SSLOentChunk / SSLAdatChunk / ... etc>
              |  | __construct()
              |  v
              |  SSLCompoundChunk (such as OENT, breaks down into more chunks)
              |   | <the entire SSLChunkParser stack recursively from SSLChunkParser down>
              |   
              v
              SSLStructChunk (such as ADAT. contains actual data).
          
At this point, the $tree object contains all of the raw data separated into 
chunks which know what they are and how to extract data from themselves.

The full data is not actually parsed and extracted from the $tree object until someone 
calls `$tree->getTracks()`. However, as the `SSLHistoryFileDiffMonitor` generates diffs,
the next thing it does is to get the tracks from this DOM and the previous DOM and
diff them. The data extraction part looks like this:

    SSLHistoryFileDiffMonitor
     | getNewOrUpdatedTracksSince()
     v 
     SSLHistoryDom
       | getTracks() // gets all data but filters it just to SSLTracks
       | ->getData() 
       v 
       SSLChunk <any of the various chunk types, e.g. OENT>
         | getDataInto(new SSLStruct) // the HistoryDom knows the appropriate struct type
         |                            // e.g. it knows that an OENT ADAT needs <SSLTrack>s
         v
         SSLStruct
          | getUnpacker() // the struct knows its own unpacker (a XOUP file)
          v
          XoupLoader
            |
            v
            XoupCompiler
              |
              v
              Unpacker (a compiled subclass of Unpacker such as XOUPSSLTrackAdatUnpacker)
          
At this point, we have concrete `SSLTrack` objects.
     

# 6. THANKS & SHOUTS


Thanks:
* Jesse Ward (jw76), for beta testing and bug reports
* Jason Salaz (VxJasonxV) for the Nicecast plugin
* Dan Etherington (baseonmars), Zac Stevens (zts) and Attila Gyorffy (djliquiduk)
  for beta testing, feedback and moral support
* DJ NightLife for his support of the project!
* Brian Tiger Chow for bug reports and patches
* Nick Masi (N-Masi) for code contributions to logging

Shouts:
* Last.fm for letting me use the back room of the office to broadcast loud radio
  shows. Oh, and for employing me. Thanks!
* Donnovan, Louis and Daniel at Bassdrive (bassdrive.com)
* Mike Louth at Digitally Imported (di.fm)


# 7. FAQ

## Does it work with Traktor?

No. I thought the clue was in the name.

I'm sure there's already an app which does this, or at least some of this,
for Traktor. To be honest, I'm out of touch with Traktor. 

## Can you make it work with Traktor?

No. I don't use Traktor, and I haven't used it in about 20 years. (That is
quite a painful realisation to write down.)

## Aww. Not even for me?

_(This is not a frequently asked question)_

## It seems to post Now Playing messages at weird times. How does it decide? How does it arrive at its "best guess"?

The main limitation to this app is what information we can get out of Serato. 
The information we can get from Serato corresponds more or less to what is 
happening in the "History" tab. Most importantly, the app learns most about 
what's going on **when a track is loaded or ejected**.

Once a track is loaded, a timer starts, and the track moves through three phases:
* first 30 seconds: "`loaded`" 
* 30 seconds to half-the-song-length: "`playing`". (_eligible_ for a now
  playing message, if another deck isn't playing.)
* half-the-song-length - full-song-length: "`scrobbleable`". This state is
  mainly for Last FM. the song will definitely be scrobbled.
* any time after full-song-length: "`finished`". This song is treated as if it
  was ejected.

The deck which is loaded first is considered first, and as long as it's still 
playing (not empty, not finished), we don't move on to the next eligible song. 
As soon as the track ends or is ejected, the next loaded deck is considered and
that's when a Now Playing event is sent.

## It's posting the Now Playing message too early and giving the game away! How can I delay this?

The problem is really that the timer starts as soon as you load the song, even
though it will take you some time to bring it into the mix. So the "now playing"
event happens earlier than most people would like. It has no idea when you 
actually start playing, it has to guess from the time you load the track.

Currently your best option is to only load a track right before you want to play it.

A future version will provide more options to address this, as it's annoying I agree.

## It's posting the Now Playing message way too late - not until I load the next song!

Once you're done with a track, if you eject it off the deck, and that will make
it obvious to the app which is the one that's playing.

## It has a lot of messages on the screen which say WARNING. How bad is that?

Most of the time you can ignore the screen. It will be hidden behind Serato
anyway. You should check that it is doing what you need - is it tweeting to
twitter? Is it posting to your Discord? Can you find the nowplaying.txt file?

Assuming it's all doing what you want, then you can basically ignore the 
messages entirely.

Read the messages if it's not doing what you want and then we can troubleshoot 
from there. There are some troubleshooting tips in the eponymous section of 
this README.

## Will it interfere with OBS? My stream overlay is resource hungry. 

This doesn't run on your OBS machine, it runs on your Serato DJ machine. 
So, no.

## How much resource does it need to run? Does it use much memory?

It uses hardly any of either. About 4Mb of RAM (basically nothing) and
a tiny amount of CPU.

## I want to use it but I'm scared of Terminal/Command Prompt.

That's not a question.

## I want to use it but it looks too complicated.

That's also not a question.

## Ok fine - what I'm really asking is how will I possibly ever remember how to use it when I have to type commands in?

First of, I'd like to thank you for not asking the two non-questions above and
getting to the point.

As of 2020, there is now a actual app (with a nice "vinyl" icon) for macOS.
It's not pretty, but it will guide you through what to do.

For Windows users, I mean, look. The first time you set it up there are maybe
5 steps, but every time you run it again in the future there's really only
2 steps: open `cmd`, and then find where you wrote down how you started it
last time, and type it again. (Put it in a Notepad txt file and copy-paste
it.) I believe in you!

## It would be really great if you had a YouTube tutorial.

I actually agree with you there. I should really do this some time, if only to
make a soothing point to people who have asked the first 2 questions. 😇

## Can you show me how to set it up?

Sure!

## Why on earth did you write it in PHP?

Sigh, not this question again. _*Walks out of interview*_

## _*Runs after you*_ but WHY?

Look, it was 2010, that's what I did in 2010. I'd never do such a ridiculous
thing now, but I'm also done being embarrassed about it and have decided to
lean into this monstrosity. It's really good code, go read it. Some of it's
extremely over-engineered, and I hope it hurts your eyes.

Please feel free to rewrite the whole thing, I'll never get round to it.

Oh… although… having said that… I _am_ also the author of
[CDJScrobbler](https://github.com/ben-xo/cdjscrobbler) which fundamentally
works in a very similar way, but is written in Java. So, that's probably how
I'd do it, as Java, I've heard, has optional clicky buttons.

# 8. CREDITS & LICENSE


SSLScrobbler is Free Open Source Software by Ben XO.
SSLScrobbler is licensed under the MIT license.

SSLScrobbler includes the following external libraries:

* php-growl (https://github.com/tylerhall/php-growl/)
  originally by Tyler Hall, licensed under the MIT license.

* PHP-Scrobbler (https://github.com/ben-xo/PHP-Scrobbler/)
  originally by Mickael Desfrenes, licensed under the LGPL license.
  
* PHP Lastfm API (https://github.com/matt-oakes/PHP-Last.fm-API/)
  originally by Matt Oakes, licensed under the MIT license.
  
* Twitter OAuth (https://github.com/abraham/twitteroauth/)
  originally by Abraham Williams, licensed under the MIT license.
  
* Twitter-PHP (https://github.com/dg/twitter-php)
  originally by David Grudl, licensed under the New BSD license.
  
* getID3 (https://github.com/JamesHeinrich/getID3/)
  originally by James Heinrich, licensed under the GPL license.
  
* vgd.php (https://v.gd/developers.php)
  originally by Richard West, released into the public domain.

* php-discord-sdk (https://github.com/cubiclesoft/php-discord-sdk)
  originally by CubicleSoft, licensed under the MIT license.

* vinyl.icns (https://findicons.com/icon/41917/vinyl)
  originally by Nando Design Studio (Fernando Albuquerque), license Freeware Non-commercial

The "NowPlaying" plugin was originally, written for [Music Hack Day Boston](http://musichackdayboston.pbworks.com/w/page/31299401/sQRatchLive) but the demo functionality is years obsolete and was removed in 2022.


