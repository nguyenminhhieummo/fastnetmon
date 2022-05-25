<?php
//program to remotely upgrade Junos

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

//creating rpc for upgrading Junos
 $rpc = "<rpc>";
 $rpc.="<request-package-add>";
 $rpc.= "<package-name>";
 $rpc.= "....path/of/package.......";
 $rpc.= "</package-name>";
 $rpc.= "<no-copy/>";
 $rpc.="<no-validate/>";
 $rpc.="</request-package-add>";
 $rpc.="</rpc>";
 $rpc.="]]>]]>\n";
echo "\nexecuting rpc \n\n";

//run the rpc from execute_rpc method
$reply=$d->execute_rpc($rpc);
echo $reply->to_string();

//reboot the system
echo"\n\nrebooting the system\n";
$d->reboot();

//close the device
echo"\nclosing\n";
 $d->close();
?>

