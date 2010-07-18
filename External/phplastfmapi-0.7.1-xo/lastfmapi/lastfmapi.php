<?php
// Include helper classes
require 'class/apibase.php';
require 'class/socket.php';
require 'class/cache.php';

// Include all files of the API
// TODO: Allow some to be missing
require 'api/album.php';
require 'api/artist.php';
require 'api/auth.php';
require 'api/event.php';
require 'api/geo.php';
require 'api/group.php';
require 'api/library.php';
require 'api/playlist.php';
require 'api/radio.php';
require 'api/tag.php';
require 'api/tasteometer.php';
require 'api/track.php';
require 'api/user.php';
require 'api/venue.php';

?>