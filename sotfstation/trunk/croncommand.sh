#!/bin/sh
killall sox 
sox -t ossdsp -w -s -r 44100 -c 2 /dev/dsp -t raw - | lame -x -m s - /var/www/sotfstation/uncutaudio/$1_lo.mp3
chmod 666 /var/www/sotfstation/uncutaudio/$1_lo.mp3