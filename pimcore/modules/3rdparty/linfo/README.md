# Linfo - PHP server health/stats script 

 - License: GNU General Public License
 - Author: Joe Gillotti <joe@u13.net>
 - Additional author(s): See AUTHORS
 - Github URL: https://github.com/jrgp/linfo
 - Sourceforge Project: https://sourceforge.net/projects/linfo
 - Ohloh SCM stats: https://www.ohloh.net/p/linfo
 - See DEVELOPERS.md for contributing

![Travis tests](https://api.travis-ci.org/jrgp/linfo.svg)

### Currently runs on:
 - Linux
 - FreeBSD
 - NetBSD
 - OpenBSD
 - DragonflyBSD
 - Darwin/Mac OSX
 - SunOS/Solaris(alpha)
 - Minix (alpha/pointless)
 - Windows

### Stuff it reports (all 100% optional; see config file):
 - CPU type/speed; Architecture
 - Mount point usage
 - Hard/optical/flash drives
 - Hardware Devices
 - Network devices and stats
 - Uptime/date booted
 - Hostname
 - Memory usage (physical and swap, if possible)
 - Temperatures/voltages/fan speeds
 - RAID arrays
 - Via official extensions:
   - Truecrypt mounts
   - DHCPD leases
   - Samba status
   - APC UPS status
   - Transmission torrents status
   - Soldat server status
   - CUPS printer status
   - IPMI 
   - libvirt VMs
   - more

### Etymology:
 - The name 'Linfo' was decided upon before I intended it to be cross platform.
   It was originally only going to be for Linux, and hence I called it Linux-Info,
   and Linfo sounded really catchy. It stuck.

### Goals: 
 - Provide info such as disk space, temperatures, cpu, ram, etc
 - Run extremely fast on old hardware; parse files rather than calling external binaries

### Usable clients:
 - Any web browser should work, including text only ones
 - Tested with: Firefox (1.x+), iOS Safari, Chrome, IE (6+), Opera, Lynx, Elinks, Dillo

### Global System requirements: 
 - At least PHP 5
 - Access to php's preg (PCRE) library, specifically preg_match() 
   and preg_match_all(). You most likely already have this.
 - note: PCRE is Linfo's only php requirement :)

Windows system requirements:
 - You need to have COM enabled - http://www.php.net/manual/en/class.com.php

Linux system requirements:
 - /proc and /sys mounted appropriately, and readable by PHP
 - Tested with the 2.6.x/3.x series of kernels.

FreeBSD system requirements:
 - PHP able to execute usual programs under /bin, /usr/bin, /usr/local/bin, etc
 - Known to work under 8.0-RELEASE; older versions may work

NetBSD system requirements:
 - PHP able to execute usual programs under /bin, /usr/bin, /usr/local/bin, /usr/pkg/bin, etc
 - Known to work under NetBSD 5.0.2; older versions may work

OpenBSD system requirements/notes:
 - PHP able to execute usual programs under /bin, /usr/bin, /usr/local/bin,  etc
 - Known to work under OpenBSD 4.7; older versions may work
 - It will not work under the default httpd chroot

### Installation/usage:
 1. Extract tarball contents to somewhere under your web root
 2. Rename sample.config.inc.php to config.inc.php, after optionally changing values in it
 3. Visit page in web browser
 4. Pass URL to your friends to show off

For other forms of output, aside from usual HTML, append the following to the URL:
 - ?out=xml - XML output (requires SimpleXML extension)
 - ?out=json - JSON output
 - ?out=jsonp&callback=functionName - JSON output with a function callback. (Look here: http://www.json-p.org/ )
 - ?out=php_array - PHP serialized associative array
 - ?out=html - Usual lightweight HTML (default)
 - ncurses (very alpha) - call ./linfo from the CLI. --nocurses to disable

Troubleshooting:
 - Try setting $settings['show_errors'] to true in the config file to yield 
   useful error messages. 

Linux system temps/voltages/etc requirments:
 - Have hddtemp listening or periodically writing to syslog, and/or
 - Have mbmon listening, and/or
 - Have sensord periodically writing to syslog and/or
 - Have hwmon set up in /sys. This is enabled by default and works by default 
   at least on recent ubuntu versions
 
For cups/samba/truecrypt/extension support:
 - Look for files named class.ext_something_.php in the lib/ folder and open them up in a text editor
   to see their instructions for use

### TODO:
 - Support for other Unix operating systems (Hurd, IRIX, AIX, HP UX, etc)
 - Support for strange operating systems: Haiku/BeOS
 - More superfluous features/extensions

### Contact (diffs/translations/etc):
 - Joe Gillotti <joe@u13.net>  (I promise I'll reply)
 - IRC - #linfo @ freenode
 - Please email to joe@u13.net instead of contacting me over SourceForge.
 - If you like and use Linfo, all I ask is that you send me suggestions or at least a thank you

