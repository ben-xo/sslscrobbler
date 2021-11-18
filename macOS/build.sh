#!/bin/bash

set -e

if [[ `which platypus` == "" ]]; then
    echo "Please install platypus through Mac Homebrew"
fi

if [[ `which php` == "" ]]; then
    echo "Please install php through Mac Homebrew"
fi

VERSION=$(head -n 1 ../README.md | cut -d' ' -f3)

platypus -i 'vinyl.icns' \
         -a 'SSL Scrobbler' \
         -o 'Text Window' \
         -p "$(which php)" \
         -V $VERSION \
         -I am.xo.SSLScrobbler \
         -f '../SSL' -f '../config.php-default' -f '../External' -f '../historyreader.php' -f '../README.md' -f '../LICENSE' \
         -C '--prompt-osascript|--immediate' \
         '../historyreader.php' $@
