<?php

error_reporting(0);
//ini_set('expect.loguser', 0);
// if set it off, then user will not be able to see what server is sending,like capabilities and other information.

include('CommitException.php');
include('LoadException.php');
include('XML.php');
include('NetconfException.php');

class Device {
    var $hostName;
    var $userName;
    var $password;
    var $port;
    var $helloRpc;
    var $stream;
    var $is_connected;
    var $last_rpc_reply;
    var $connectTimeout;
    var $replyTimeout;
    
    /**
    * A <code>Device</code> is used to define a Netconf server.
    * <p>
    * Typically, one
    * <ol>
    * <li>creates a {@link #Device(String,String,String) Device} 
    * object.</li>
    * <li>perform netconf operations on the Device object.</li>
    * <li>Finally, one must close the Device and release resources with the 
    * {@link #close() close()} method.</li>
    * </ol>
    */
    public function __construct(){
       if(func_num_args() ==1 && is_array(func_get_arg(0)) )
	{
	$this->Device_array(func_get_arg(0));
	}
	else
	{
	$this->Device_string(func_get_args());
	}
    }
    
    /** This function is called when user passes list of string as arguments
    * while creating object of Device class
    */
    public function Device_string($arr){
	if(count ($arr) == 6) {
           if(is_array($arr[3])) {
                $this->hello_rpc = $this->create_hello_rpc($arr[3]);
                $this->port = 830;
            }
           else {
                $this->port = $arr[3];
                $this->hello_rpc = $this->default_hello_rpc();
            }
        }
        else if (count ($arr) == 5)
        {
            if (is_array($arr[3])) {
                $this->hello_rpc = $this->create_hello_rpc($arr[3]);
                $this->port = $arr[4];
            }
            else {
                $this->port = $arr[3];
                $this->hello_rpc = $this->create_hello_rpc($arr[4]);
            }
        }
	else {
            $this->port = 830;
            $this->hello_rpc = $this->default_hello_rpc();
        }
        $this->hostName = $arr[0];
        $this->userName = $arr[1];
        $this->password = $arr[2];
	$this->connectTimeout = 10;
	$this->replyTimeout =600;
        $this->is_connected = false;
    }
 
    /** This function is called when user passes argument as array,
    *  while creating object of Device class
    */
    public function Device_array(array $params)
    {
     if( $params["hostname"]!=null && !(empty($params["hostname"])) && (is_string($params["hostname"])))  		{
	$this->hostName = $params["hostname"];		
 	}
	else{
	die ("host name should be string and should not be empty or null\n");
	}

	if (empty($params["username"]) || is_null( $params["username"] ) ){
	die ("user name should not be empty or null\n");
	}
	else{
	$this->userName = $params["username"];
	}

	if (empty($params["password"]) || is_null( $params["password"] ) ){
	die("user name should not be empty or null\n");
	}
	else{
	$this->password = $params["password"];
	}

	if($params["port"]!=null && !(empty($params["port"])) && is_numeric($params["port"]) )
	{
	$this->port = $params["port"];
	}
	else{
	$this->port = 830;
	}

	if ( $params["capability"]!=null && ! (empty($params["capability"]) ) ){
	$this->hello_rpc= $this->create_hello_rpc($params["capability"]);
	}
	else {
	$this->hello_rpc = $this->default_hello_rpc();
	}
        $this->connectTimeout=10;
	$this->replyTimeout= 600;
	$this->is_connected =false;
     }
 	
    /**
    *Prepares a new <code?Device</code> object, either with default 
    *client capabilities and default port 830, or with user specified
    *capabilities and port no, which can then be used to perform netconf 
    *operations.
    */
    public function connect() {

    return $this->userName." ok";
    // $this->stream = expect_popen("ssh -o ConnectTimeout=$this->connectTimeout $this->userName@$this->hostName -p $this->port -s netconf");        
    $this->stream = expect_popen("ssh -o ConnectTimeout=$this->connectTimeout $this->userName@$this->hostName -p $this->port -s netconf"); 
    // return "hieu "; 

    // $this->stream = expect_open("ssh root@103.125.170.114 -p 880 -s netconf");
    // return 0;
	ini_set('expect.timeout',  $this->replyTimeout);
    // ssh root@103.125.170.114 -p 880 -s netconf
	$flag = true;        
	while ($flag) {
        switch (expect_expectl($this->stream,array (
                array("Password:","PASSWORD"),
                array("password:","PASSWORD"),
                array("yes/no)?","YESNO"),
                array("passphrase","PASSPHRASE"),
                array("]]>]]>","NOPASSPHRASE"),
                array(" ","SHELL"),
                  ))) {
                case "PASSWORD":
		    fwrite($this->stream,$this->password."\n");
                    switch (expect_expectl($this->stream,array (
                        array("Password:","PASSWORD"),
                        array("password:","PASSWORD"),
                        array("]]>]]>","hello"),
                        ))) { 
                        case "PASSWORD":
                            throw new NetconfException("Wrong username or password");
                        case "hello":
                            $this->send_hello($this->hello_rpc);
                            break;
                    }
                    $flag = false;
                    break;
                case "PASSPHRASE":
                    fwrite($this->stream,$this->password."\n");
                    switch (expect_expectl($this->stream,array (
                        array("Password:","PASSWORD"),
                        array("password:","PASSWORD"),
                        array("]]>]]>","hello"),
                        ))) {
                        case "PASSWORD":
                            throw new NetconfException("Wrong username or password");
                        case "hello":
                            $this->send_hello($this->hello_rpc);
                            break;
                    }
                    $flag = false;
                    break;
                case "NOPASSPHRASE":
                    $this->send_hello($this->hello_rpc);
                    $flag = false;
                    break;
                case "YESNO":
                    fwrite($this->stream,"yes\n");  // default value of yes for for new netconf host
                    break;
                case "SHELL":
                    break;
		case EXP_EOF :
		    throw new NetconfException("Timeout Connecting to device");
                default:
                    throw new NetconfException("Device not found/ unknown error occurred while connecting to Device");
                }
        }
        $this->is_connected = true;
    }

    /**
    *Sends the Hello capabilities to the netconf server.
    */
    private function send_hello($hello) {
      $reply = "";
      $reply = $this->get_rpc_reply($hello);
      $serverCapability = $reply;
      $this->last_rpc_reply = $reply;
    }

    /**
    *Sends the RPC as a string and returns the response as a string.
    */
    private function get_rpc_reply($rpc) {
	$rpc_reply = "";
	fwrite($this->stream,$rpc."\n");
	while (1) {
            $line = fgets($this->stream);
            if (strncmp($line,"<rpc>",5)==0)
                if (strpos($line,"]]>]]>"))
                    continue;    
                else {
                 while (1)
		{
			$line = fgets($this->stream);
			if (strpos($line,"]]>]]>"))
			 {
					
                            $line = fgets($this->stream); 
                            break;
                          }
                }
                }
            if ((strncmp($line,"]]>]]>",6))==0)
                break;
            $rpc_reply.=$line;
        }
        return $rpc_reply;
    }

    /**
    *Sends RPC(as XML object or as a String) over the default Netconf session 
    *and get the response as an XML object.
    *<p>
    *@param rpc
    *       RPC content to be sent. 
    *@return RPC reply sent by Netconf server.
    */
    public function execute_rpc($rpc) {
        if ($rpc==null)
            throw new NetconfException("Null RPC");
        if (gettype($rpc) == "string") {
            if (!$this->starts_with($rpc,"<rpc>")) {
                $rpc = "<rpc><".$rpc."/></rpc>";
                $rpc.="]]>]]>";
            }
            $rpc_reply_string = $this->get_rpc_reply($rpc);
        }
        else {
            $rpcString = $rpc->to_string();
            $rpc_reply_string = $this->get_rpc_reply($rpcString);
        }
        $this->last_rpc_reply = $rpc_reply_string;
	$rpc_reply = $this->convert_to_xml($rpc_reply_string);
	return $rpc_reply;
    }

    /**
    *Converts the string to XML.
    *@return XML object.
    */
     private function convert_to_xml($rpc_reply) {
        $dom = new DomDocument();
        $xml = $dom->loadXML($rpc_reply);
        if (!$xml)
            return false;
        $root = $dom->documentElement;
        return new XML($root,$dom);
      }

    /**
    @retrun the last RPC Reply sent by Netconf server.
    */
    public function get_last_rpc_reply() {
      return $this->last_rpc_reply;
    }

    /**
    *sets the username of the Netconf server.
    *@param username
    *is the username which is to be set
    */
    public function set_username($username) {
        if ($this->is_connected)
           throw new NetconfException("Can't change username on a live device. Close the device first.");
        else
            $this->userName =   $username;
    }

    /**
    *sets the hostname of the Netconf server.
    *@param hostname
    *      is the hostname which is to be set.
    */
    public function set_hostname($hostname) {
        if ($this->is_connected)
            throw new NetconfException("Can't change hostname on a live device. Close the device first");
        else
            $this->hostName = $hostname;
    }

    /**
    *sets the password of the Netconf server.
    *@param password
    *is the password which is to be set.
    */
    public function set_password($password) {
     if ($this->is_connected)
     throw new NetconfException("Can't change the password for the live device. Close the device first");
     else
     $this->password = $password;
    }

    /**
    *sets the port of the Netconf server.
    *@param port
    *is the port no. which is to be set.
    */
    public function set_port($port) {
      if ($this->is_connected)
      throw new NetconfException("Can't change the port no for the live device. Close the device first");
      else
      $this->port = $port;
    }

    /**
    *Set the client capabilities to be advertised to the Netconf server.
    *@param capabilities 
    *Client capabilities to be advertised to the Netconf server.
    *
    */
     public function setCapabilities($capabilities) {
        if($capabilities == null)
            die("Client capabilities cannot be null");
        if($this->is_connected) {
            throw new NetconfException("Can't change clien capabilities on a live device. Close the
             device first.");
        $this->helloRpc = $this->create_hello_rpc($capabilities);
        }
    }

    /**
    * set connectTimeout of the Netconf server
    * @param connectTimeout
    * is the connection timeout which is to be set
    */
    public function setConnectTimeout($ctime){
      if($this->is_connected)
      throw new NetconfException("Can't change connect timeout value for live device. Close the device first");
      else
      $this->connectTimeout= $ctime;
       }

    /**
    * set replyTimeout of the Netconf server
    * @param replyTimeout
    * is the reply timeout in which reply should come from server
    */
    public function setReplyTimeout($rtime){
      if($this->is_connected){
      throw new NetconfException("Can't change reply timeout value for live device. Close the device first");}
      else
      $this->replyTimeout=$rtime;
	}

    /**
    *Check if the last RPC reply returned from Netconf server has any error.
    *@return true if any errors are found in last RPC reply.
    */
     public function has_error() {
        if(!$this->is_connected)
            throw new NetconfException("No RPC executed yet, you need to establish a connection first");
        if ($this->last_rpc_reply == "" || !(strstr($this->last_rpc_reply,"<rpc-error>")))
            return false;
        $reply = $this->convert_to_xml($this->last_rpc_reply);
        $tagList[0] = "rpc-error";
        $tagList[1] = "error-severity";
        $errorSeverity = $reply->find_value($tagList);
        if ($errorSeverity != null && $errorSeverity == "error")
            return true;
        return false;
    }

    /**
    *Check if the last RPC reply returned from Netconf server has any warning.
    *@return true if any warnings are found in last RPC reply.
    */
    public function has_warning() {
        if(!$this->is_connected)
            throw new NetconfException("No RPC executed yet, you need to establish a connection first");
        if ($this->last_rpc_reply == "" || !(strstr($this->last_rpc_reply,"<rpc-error>")))
            return false;
        $reply = $this->convert_to_xml($this->last_rpc_reply);
        $tagList[0] = "rpc-error";
        $tagList[1] = "error-severity";
        $errorSeverity = $reply->find_value($tagList);
        if ($errorSeverity != null && $errorSeverity == "warning")
            return true;
        return false;
    }

    /**
    *Check if the last RPC reply returned from the Netconf server.
    *contain &lt;ok&gt; tag
    *@return true if &lt;ok&gt; tag is found in last RPC reply.
    */
    public function is_ok() {        
        if(!$this->is_connected)
            throw new NetconfException("No RPC executed yet, you need to establish a connection first");
        if ($this->last_rpc_reply!=null && strstr($this->last_rpc_reply,"<ok/>"))
            return true;
        return false;
    }

    /**
    *Locks the candidate configuration.
    *@return true if successful.
    */
     public function lock_config() {
        $rpc = "<rpc>";
        $rpc.= "<lock>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="</lock>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            return false;
        return true;
    }

    /**
    *Unlocks the candidate configuration.
    *@return true if successful.
    */
    public function unlock_config() {
        $rpc = "<rpc>";
        $rpc.="<unlock>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="</unlock>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            return false;
        return true;
    }

     private function starts_with($string,$substring) {
        trim($substring);
        trim($string);
        $length = strlen($substring);
        if (substr($string,0,$length)===$substring)
            return true;
        return false;
    }

    /**
    *Loads the candidate configuration, Configuration should be in XML format.
    *@param configuration
    *        Configuration, in XML fromat, to be loaded. For eg:
    *        &lt;configuration&gt;&lt;system&gt;&lt;services&gt;&lt;ftp/&gt;&lt;/services&gt;&lt;/
             system&gt;&lt;/configuration&gt;
    *       will load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *       You can choose "merge" or "replace" as the loadType.
    */
    public function load_xml_configuration($configuration,$loadType) {
        if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            throw new NetconfException("'loadType' argument must be merge|replace\n");
        if ($this->starts_with($configuration,"<?xml version"))
            $configuration = preg_replace('/\<\?xml[^=]*="[^"]*"\?\>/', "", $configuration);
        else if (!($this->starts_with($configuration,"<configuration>")))
            $configuration = "<configuration>".$configuration."</configuration>";
        $rpc = "<rpc>";
        $rpc.="<edit-config>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="<default-operation>";
        $rpc.=$loadType;
        $rpc.="</default-operation>";
        $rpc.="<config>";
        $rpc.=$configuration;
        $rpc.="</config>";
        $rpc.="</edit-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Loads the candidate configuration, Configuration should be in text/tree format.
    *@param configuration 
    *      Configuration, in text/tree format, to be loaded. 
    *      For example,
    *       "system{
    *          services{
    *              ftp;
    *           }
    *       }"
    *       will load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *        You can choose "merge" or "replace" as the loadType.
    */
     public function load_text_configuration($configuration,$loadType) {
        if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            throw new NetconfException ("'loadType' argument must be merge|replace\n");
	$rpc = "<rpc>";
        $rpc.="<edit-config>";
        $rpc.="<target>";
        $rpc.="<candidate/>";
        $rpc.="</target>";
        $rpc.="<default-operation>";
        $rpc.=$loadType;
        $rpc.="</default-operation>";
        $rpc.="<config-text>";
        $rpc.="<configuration-text>";
        $rpc.=$configuration;
        $rpc.="</configuration-text>";
        $rpc.="</config-text>";
        $rpc.="</edit-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
	$this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Loads the candidate configuration, Configuration should be in set format.
    *NOTE: This method is applicable only for JUNOS release 11.4 and above.
    *@param configuration
    *       Configuration, in set format, to be loaded. For example,
    *       "set system services ftp"
    *       will load 'ftp' under the 'systems services' hierarchy.
    *To load multiple set statements, separate them by '\n' character.
    */
     public function load_set_configuration($configuration) {
	$rpc = "<rpc>";
        $rpc.="<load-configuration action=\"set\">";
        $rpc.="<configuration-set>";
        $rpc.=$configuration;
        $rpc.="</configuration-set>";
        $rpc.="</load-configuration>";
        $rpc.="</rpc>";
	$rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
	if ($this->has_error() || !$this->is_ok())
            throw new LoadException("Load operation returned error");
    }

    /**
    *Commit the candidate configuration.
    */
     public function commit() {
        $rpc = "<rpc>";
        $rpc.="<commit/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n"; 
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            throw new CommitException("Commit operation returned error");
    }

    /**
    *Commit the candidate configuration, temporarily. This is equivalent of
    'commit confirm'
    *@param seconds 
    *        Time in seconds, after which the previous active configuratio
    *        is reverted back to.
    */
     public function commit_confirm($seconds) {
        $rpc = "<rpc>";
        $rpc.="<commit>";
        $rpc.="<confirmed/>";
        $rpc.="<confirm-timeout>".$seconds."</confirm-timeout>";
        $rpc.="</commit>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            throw new CommitException("Commit operation returned error");
    }

    /**
    *Validate the candidate configuration.
    *@return true if validation successful.
    */
     public function validate() {
        $rpc = "<rpc>";
        $rpc.="<validate>";
        $rpc.="<source>";
        $rpc.="<candidate/>";
        $rpc.="</source>";
        $rpc.="</validate>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        if ($this->has_error() || !$this->is_ok())
            return false;
        return true;
    }

    /**
    *Reboot the device corresponding to the Netconf Session.
    *@return RPC reply sent by Netconf servcer.
    */
     public function reboot() {
        $rpc = "<rpc>";
        $rpc.="<request-reboot/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        return $rpcReply;
    }

    /**
    *This method should be called for load operations to happen in 'private' mode.
    *@param mode
    *       Mode in which to open the configuration.
    *       Permissible mode(s) : "private"
    */
     public function open_configuration($mode) {
        $rpc = "<rpc>";
        $rpc.="<open-configuration>";
        $rpc.="<";
	$rpc.=$mode;
	$rpc.="/>";
        $rpc.="</open-configuration>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
	}

    /**
    *This method should be called to close a private session, in case its started.
    */
     public function close_configuration() {
        $rpc = "<rpc>";
        $rpc.="<close-configuration/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
    }

    /**
    *Run a cli command.
    *NOTE: The text utput is supported for JUNOS 11.4 and alter.
    *@param command
    *       the cli command to be executed.
    *@return result of the command,as a String.
    */
     public function run_cli_command() {
        $rpcReply = "";
        $format = "text";
        if(func_num_args() == 2)
            $format = "html";
        $rpc = "<rpc>";
        $rpc.="<command format=\"text\">";
        $rpc.=func_get_arg(0);
        $rpc.="</command>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        trim($rpcReply);
        $xmlreply = $this->convert_to_xml($rpcReply);
        if (!$xmlreply) {
            echo "RPC-REPLY is an invalid XML\n";
            return null;
        }
        $tags[0] = "output";
        $output = $xmlreply->find_value($tags);
        if ($output != null) 
            return $output;
        return $rpcReply;
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in XML format.
    *@param configFilu 
    *       Path name of file containing configuration,in xml format,
    *       ro be loaded.
    *@param loadType
    *       You can choose "merge" or "replace" as the loadType.
    */
     public function load_xml_file($configFile,$loadType) {
	$configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            throw new NetconfException ("File not found error");
        while(!feof($file))
        {
            $line=fgets($file);
            $configuration.=$line;
        }
        fclose($file);
        if ($loadType == null ||(!($loadType == "merge") && !($loadType == "replace")))
            throw new NetconfException("'loadType' must be merge|replace");
        $this->load_xml_configuration($configuration,$loadType);
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in text/tree format.
    *@param configFile
    *      Path name of file containining configuration, in xml format,
    *      to be loaded.
    *@param loadType
    *      You can choose "merge" or "replace" as the loadType.
    */
     public function load_text_file($configFile,$loadType) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            throw new NetconfException("File not found error");
        while ($line = fgets($file))
            $configuration.=$line;
        fclose($file);
	if ($loadType == null || (!($loadType == "merge") && !($loadType == "replace")))
            throw new NetconfException("'loadType' argument must be merge|replace\n");
        $this->load_text_configuration($configuration,$loadType);
    }

    /**
    *Loads the candidate configuration from file,
    *configuration should be in set format.
    *NOTE: This method is applicable only for JUNOS release 11.4 and above.
    *@param configFile
    *     Path name of file containing configuration, in set format, 
    *     to be loaded.
    */
     public function load_set_file($configFile) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            throw new NetconfException("File not found error");
        while ($line = fgets($file))
            $configuration.=$line;
        fclose($file);
        $this->load_set_configuration($configuration);
    }

      private function get_config($target,$configTree) {
        $rpc = "<rpc>";
        $rpc.="<get-config>";
        $rpc.="<source>";
        $rpc.="<".$target."/>";
        $rpc.="</source>";
        $rpc.="<filter type=\"subtree\">";
        $rpc.=$configTree;
        $rpc.="</filter>";
        $rpc.="</get-config>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        return $rpcReply;
    }

    /**
    *Retrieve the candidate configuration, or part of the configuration.
    *If no argument is specified, then the
    *configuration is returned for
    *&gt;<configuration$gt;&lt;/configuration&gt;  
    *else 
    *For example, to get the whole configuration, argument should be 
    *&lt;configuration&gt;&lt;/configuration&gt;
    *return configuration data as XML object.
    */
     public function get_candidate_config() {
        if(func_num_args() == 1)
            return $this->convert_to_xml($this->get_config("candidate",func_get_arg(0)));
        return $this->convert_to_xml($this->get_config("candidate","<configuration></configuration>"));
    }

    /**
    *Retrieve the running configuration, or part of the configuration.
    *If no argument is specified then 
    *configuration is returned for
    *&lt;configuration&gt;&lt;/configuration&gt;
    *else
    *For example, to get the whole configuration, argument should be 
    *&lt;configuration&gt;&lt;/configuration&gt;
    @return configuration data as XML object.
    */
     public function get_running_config() {
        if (func_num_args() ==1)
            return $this->convert_to_xml($this->get_config("running",func_get_arg(0)));
        return $this->convert_to_xml($this->get_config("running","<configuration></configuration>"));
    }

    /**
    *Loads and commits the candidate configuration, Configuration can be in text/xml/set foramt.
    *@param configFile
    *      Path name of file containing configuration, in text/xml/set format,
    *      to be loaded. For example,
    *"system{
    *    services{
    *        ftp;
    *    }
    *}"
    *will load 'ftp' under the 'systems services' hierarchy.
    *OR
    *&lt;configuration&gt;&lt;system&gt;&lt;serivces&gt;ftp&lt;/services&gt;&lt;/system&gt;&lt;/
     configuration&gt;
    *will load 'ftp' under the 'systems services' hierarchy.
    *OR
    *"set system services ftp"
    *wull load 'ftp' under the 'systems services' hierarchy.
    *@param loadType
    *     You can choose "merge" or "replace" as the loadType.
    *NOTE : This parameter's value is redundant in case the file contains 
    *configuration in 'set' format.
    */
     public function commit_this_configuration($configFile,$loadType) {
        $configuration = "";
        $file = fopen($configFile,"r");
        if (!$file)
            throw new NetconfException ("File not found");
        while( $line = fgets($file))
            $configuration.=$line;
        trim($configuration);
        fclose($file);
        if ($this->lock_config()) {
            if ($this->starts_with($configuration,"<"))
                $this->load_xml_configuration($configuration,$loadType);
            else if ($this->starts_with($configuration,"set"))
                $this->load_set_configuration($configuration);
            else
                $this->load_text_configuration($configuration,$loadType);
            $this->commit();
            $this->unlock_config();
        }
        else
            throw new NetconfException ("Unclean lock operation. Cannot proceed further");
    }

    /**
    *Closes the Netconf session
    */
     public function close() {
	$rpc = "<rpc>";
        $rpc.="<close-session/>";
        $rpc.="</rpc>";
        $rpc.="]]>]]>\n";
        $rpcReply = $this->get_rpc_reply($rpc);
        $this->last_rpc_reply = $rpcReply;
        fclose($this->stream);  
        $this->is_connected = $this->is_ok() ? false : true;
     }
    /**
     * Create hello_rpc packet with user defined capabilities
     * @param capabilities
     * capabilities specified by user
     */
      private function create_hello_rpc(array $capabilities) {
        $hello_rpc = "<hello>\n";
        $hello_rpc.="<capabilities>\n";
        foreach ($capabilities as $capIter) {
            $hello_rpc.="<capability>".$capIter."</capability>\n";
        }
        $hello_rpc.="</capabilities>\n";
        $hello_rpc.="</hello>\n";
        $hello_rpc.="]]>]]>\n";
        return $hello_rpc;
    }

    /**
     * function to generate default capabilities of client
     */
     private function get_default_client_capabilities() {
        $defaultCap[0] = "urn:ietf:params:xml:ns:netconf:base:1.0";
        $defaultCap[1] = "urn:ietf:params:xml:ns:netconf:base:1.0#candidate";
        $defaultCap[2] = "urn:ietf:params:xml:ns:netconf:base:1.0#confirmed-commit";
        $defaultCap[3] = "urn:ietf:params:xml:ns:netconf:base:1.0#validate";
        $defaultCap[4] = "urn:ietf:params:xml:ns:netconf:base:1.0#url?protocol=http,ftp,file";
        return $defaultCap;
    }

    /**
     *  function to generate default hello_rpc packet.
     *  It calls get_default_client_capabilities() function to generate default capabilites of client
     */
     private function default_hello_rpc() {
        $defaultCap = $this->get_default_client_capabilities();
        return $this->create_hello_rpc($defaultCap);
    }
    
    /**
     * method missing function
     * It is called when some operation command is called directly 
     * For Example
     * $device_name->get_alarm_information()
     * this will call __call()function which will call execute_rpc("get-alarm-information")
     * It will output alarm information which can be obtained from execute_rpc("get-alarm-information")
     */        
    public function __call($function,$args){
	$change=preg_replace('/_/','-',$function);
	$reply=$this->execute_rpc($change);
	return $reply;
    } 
}
?>
