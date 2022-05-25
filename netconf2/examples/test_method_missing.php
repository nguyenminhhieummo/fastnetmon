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
echo "connected to device";

//getting alarm information without calling execute_rpc method
$inven=$d->get_alarm_information();
echo $inven->to_string();

//closing device
$d->close();
echo "device closed";
?>

