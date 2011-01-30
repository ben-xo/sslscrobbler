This plugin folder contains a simple web page (index.html) that will show 
you the current playing track in ScratchLive! as it is playing. You WILL need to
serve the page through Apache, though, not just open it locally in a browser, as
it makes an AJAX call every few seconds to nowplaying.php to actually read out the 
result. 

Also in the plugin, but commented out, is the ability to look a buy link for the track
on 7Digital, and display it as a QR code, using The Echo Nest and Google Charts APIs.
(It's not actually that useful in practise, but it looks neat.)

Of course, if you're not using any of the fancy API stuff, there's no real reason
that it needs Apache - fixing it is left as an exercise to the reader.
