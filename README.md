# FRITZBox-SynologyLogCenterDaemon
A PHP 7.2 script that records your FRITZ!Box's System Log events in your Synology NAS, making it possible to browse your modem's events like this:

![](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/raw/master/wiki-images/fritz-log-center.png)

Due to a reported bug in FRITZ!OS 6.85 and FRITZ!OS 7.13, the script will record duplicate events in the Log Center database, making this script only useful for AVM models that receive support on FRITZ!OS 7.x

Update May 1, 2020: I noticed that the duplicate timestamps were no longer appearing with FRITZ!OS 7.15. AVM confirmed that this version contains the fix for the issue I reported.

Before you can run the script, please review the changes you need to make to your [modem](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/wiki/Settings-on-your-FRITZ!Box) and your [NAS](https://github.com/biocoder-frodo/FRITZBox-SynologyLogCenterDaemon/wiki/Settings-on-your-Synology-NAS)

The script has the following command line options:
* -l Absolute path to the Log Center databases folder.
* -p Optional, absolute path to a textfile that contains the FRITZ!Box password, defaults to ./(username).pwdfile when omitted.
* -k Optional, absolute path to certificate file of your FRITZ!Box, defaults to ./boxcert.cer when omitted.
* -t Optional, http or https, defaults to https when omitted.
* -f Optional, the name of your FRITZ!Box, other than fritz.box
* -r Optional, the name of your FRITZ!Repeater, this login only uses the password for the admin interface of your repeater.
* -u Optional, the name of your FRITZ!Box user, other than stats.
* -q Optional, fetch the eventlog once and dump it to the console.
* -d Optional, test the database connection and dump a rowcount to the console.
* -i Optional, initialises the Synology SQLite database. Logcenter has to be running and configured to listen to the port you specify.
* --udp Optional, the UDP port to contact Log Center on. The default port is 516.
* -a Optional, it appears that on DSM7 the source host is no longer in the filename of the Logcenter database. Use this option if you are on DSM 7.
