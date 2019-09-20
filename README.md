# FRITZBox-SynologyLogCenterDaemon
A PHP 7.2 script that records your FRITZ!Box's System Log events in your Synology NAS, making it possible to browse your modem's events like this:

![](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/raw/master/wiki-images/fritz-log-center.png)

Due to a reported bug in FRITZ!OS 6.85 and FRITZ!OS 7.13, the script will record duplicate events in the Log Center database, making this script only useful for AVM models that receive support on FRITZ!OS 7.x

Before you can run the script, please review the changes you need to make to your [modem](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/wiki/Settings-on-your-FRITZ!Box) and your [NAS](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/wiki/Settings-on-your-Synology-NAS)

The script has the following command line options:
* -p Absolute path to a textfile that contains the FRITZ!Box password
* -l Absolute path to the Log Center databases folder
* -f Optional, the name of your FRITZ!Box, other than fritz.box
* -u Optional, the name of your FRITZ!Box user, other than stats
* --udp Optional, the UDP port to contact Log Center on. The default port is 516