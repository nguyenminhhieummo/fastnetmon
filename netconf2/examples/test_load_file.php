<?php
include('netconf/Device.php');

/**can create Device by two ways
   by key-value pair in array
   by passing strings as argument in Device class constructor
 */   

//creating a new device and establishing NETCONF session using key value pair
$param= array("hostname"=> "abc","username"=>"xyz", "password"=>"ab12jk");
$d= new Device($param);

//creating a new device, by passing strings as argument  
//$d= new Device("hostname", "username", "passwd");

/** optional
    can set connectTimeout and replytimeout value before connecting to device
    $d->setReplyTimeout(35);
    $d->setConnectTimeout(10);    
 */

//connect to device and establish netconf session
$d->connect();
echo "\n connected to device\n";

//locking device
$islocked=$d->lock_config();

//if successfully locked than loading the configuration from file and commiting changes
if($islocked)
{
//for loading xml file
$d->load_xml_file("/path/to/file","merge");

//for loading text file
//$d->load_text_file("/path/to/file","merge");

//for loading set file
//$d->load_set_file("/path/to/file");
$d->commit();
}

//if not successfully locked then exit from the function
else
{
echo "device can not be locked";
exit(1);
}

//unlocking and closing device
$d->unlock();
$d->close();
?>

