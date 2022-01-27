# decode_wn_isk_am550_smartmeter.php
a php tribute to pocki80 to read (and write to txt-files) of Iskra AM 550 of Wiener Netze

Without pocki80 who did the work in Python 3, I wouldn't have any chance to decode and to CRC-check the gotten values! Many Thnx.

This project was started to read the smart meter values from Iskra(emeco) AM550 from Wiener Netze

"Hardware"


Raspberry Pi Zero
IR interface (Weidmann Elektronic; http://www.weidmann-elektronik.com/Produkt_IR-Kopf.html) using USB

 Software 

- OS: raspberry pi OS
- installed Apache/php  (for example in german: https://tutorials-raspberrypi.de/webserver-installation-homeverzeichnis-aendern/)

Target

to read an record data from the smart meter in a simple readable txt (csv-file) and to store it for later use for diagrams using html-pages 

Remarks

- php-pogram must be started within crontab-file with @reboot php "Filename.php"
- or alternatively within a terminal window by using 'php Filename.php'
- AM550 sends data without requests! every second!
- CRC fails to every 5th to 6th package (seconds). - I don't know why but it means anyhow more than enough data.
- This is different to other meters I read (no smart meters: L&G E350, Iskra MT174, ABB B23 112-100 and water energy-meter Sharky BR773); each of them behave differently.
- Using a raspberry pi zero I have two USB-IR-interfaces connected to L&G E350 and Iskra MT174 and a further one for ABB B23 and the Sharky BR 773. Having two USB-IR-interfaces attached it is a little tricky because sometimes /dev/ttyUSB0 and /dev/ttyUSB1 swap. using "udev" you can create a link in /dev/.

Further hint:
For some reasons it sometimes doesn't start without having executed the following bash command which I put in a file and execute it just before the start of the php-file within crontab.

stty -F /dev/ttyUSB0 9600 -parenb cs8 -cstopb -ixoff -crtscts -hupcl -ixon -opost -onlcr -isig -icanon -iexten -echo -echoe -echoctl -echoke
