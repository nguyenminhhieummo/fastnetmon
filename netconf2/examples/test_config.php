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
$str1= "set system services ftp";
$str2= "system { services {ftp; }}";
$str3= "<system><services><ftp/></services></system>";

/* loading xml configuration can be done using xml and xmlBuilder Class
$xml_builder= new XMLBuilder();
$ftp_config =$xml_builder->create_new_config("system","services","ftp");
*/

//Perform configuration change only if device is successfully locked
if($islocked)
{
//can be done by any of these three steps
//$d->load_set_configuration($str1);
//$d->load_text_configuration($str2,"merge");
$d->load_xml_configuration($str3,"merge");

// when loading xmlbuider class
//$d->load_xml_configuration($ftp_config->to_string(),"merge");
$d->commit();
}

//otherwise exit
else
{
echo "device can not be locked";
exit (1);
}

//unlocking and closing device
$d->unlock();
$d->close();
?>

