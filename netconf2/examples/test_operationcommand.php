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

//getting reply from server using execute_rpc() method
try
{
$reply=$d->execute_rpc("get-system-information");
//converting xml reply to string and printing it
echo $reply->to_string();
}

catch(Exception $e)
{
echo 'exception', $e->getMessage(), "\n";
}

//closing device
$d->close();
echo "device closed";
?>

