NETCONF-PHP
============

PHP library for NETCONF

SUPPORT
=======

This software is not officially supported by Juniper Networks,but by a team dedicated to helping customers, partners and the development community.To report bug-fixes, issues, suggestion please contact netconf-automation-hackers@juniper.net

FEATURES
========

PHP NETCONF APIs are designed to provide the same capabilties that a user would have on the Junos CLI, but in an environment built for automation tasks.These capabilities include, but are not limited to:

* Remote connectivity and management of Junos devices via NETCONF.
* Provide "facts" about the device such as software-version, serial-number etc.
* Retrieve "operational" or "run-state" information.
* Retrieve configuration information.
* Make configuration changes in unstructured and structured ways.

REQUIREMENTS
============

Installation requires Php and php-expect module. PHP NETCONF APIs are successfully tested in php5.5.3 

INSTALLATION
============
Note: These installation steps are successfully tested in Fedora 15 i686 and Centos-6.5-i386 and Ubuntu12.04LTS and higher version.

       Before installing PHP-NETCONF-API make sure that you have installed all the requirements.
       For detailed steps about installation, refer to INSTALL.md file.
	
       Download netconf-php folder in zip form at any path in your Desktop
       * wget  -O /any/path/in/Desktop/netconf-php-master.zip https://github.com/Juniper/netconf-php/archive/master.zip           
       (usually default path of php is /usr/share/php)
       * unzip netconf-php-master.zip
       * Copy netconf folder in /usr/share/php (default php path)
       include this path in your API for Device.php 
       For example while writing your code, include path 
       include('netconf/Device.php')

SYNOPSIS
========
        <?php
        include('netconf/Device.php');
        //creating a new device and establishing NETCONF session
        $d= new Device("hostname", "username", "passwd");
        $d->connect();
        echo "connected to device";
        //getting reply from server 
        try
        {
        $inven=$d->get_system_information();
        echo $inven->to_string();
        }
        catch(Exception $e)
        {
        echo 'exception', $e->getMessage(), "\n";
        }
        //closing device
        $d->close();
        echo "device closed";
        ?>

        Sample Output:
  	     <rpc-reply xmlns=".......">
             <system-information>
                 <hardware-model>olive</hardware-model>
                 <os-name>junos</os-name>
                 <os-version>13.2R4</os-version>
                 <host-name>foo</host-name>
             </system-information>
         </rpc-reply>


License
=======
(BSD 2)

Copyright © 2014, Juniper Networks

All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

(1) Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

(2) Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS “AS IS” AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the authors and should not be interpreted as representing official policies, either expressed or implied, of Juniper Networks.

Dependencies
============
The API requires installation of PHP extension for expect library.
http://pecl.php.net/package/expect

