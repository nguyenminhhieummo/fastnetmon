<?php
require_once 'netconf/Device.php';

class DeviceTest extends PHPUnit_Framework_TestCase {
    protected static $d;
    public static function setUpBeforeClass()
    {
        self::$d = new Device("hostname", "username", "password");
    }

/**
     * @covers Device::connect
     * @todo   Implement testConnect().
    */
  public function testConnect() {
    self::$d->connect();
    $conn= self::$d->last_rpc_reply;
    $this->assertGreaterThan(-1,strpos($conn, "</hello>"));       
    }

/**
     * @covers Device::execute_rpc
     * @todo   Implement testExecute_rpc().
     * @depends testConnect
     */
  public function testExecute_rpc() {
      self::$d->execute_rpc("get-alarm-information");
      $exe=self::$d->last_rpc_reply;
      $this->assertGreaterThan(-1,strpos($exe,"rpc-reply"));
      $this->assertGreaterThan(-1, strpos($exe,"alarm"));
      $this->assertFalse(strpos($exe,"error"));
        }
        
    /**
     * @covers Device::lock_config
     * @todo   Implement testLock_config().
     * @depends testConnect
*/     
   public function testLock_config() {
    self::$d->lock_config();
    $isloc=  self::$d->last_rpc_reply;
    $this->assertGreaterThan(-1,strpos($isloc,"rpc-reply"));
    $this->assertGreaterThan(-1,strpos($isloc,"<ok/>"));
    }


    /**
     * @covers Device::load_xml_configuration
     * @todo   Implement testLoad_xml_configuration().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testLoad_xml_configuration() {
        $str1= "<system><services><ftp/></services></system>";
        self::$d->load_xml_configuration($str1, "merge");
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));        
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));
    }

    /**
     * @covers Device::load_text_configuration
     * @todo   Implement testLoad_text_configuration().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testLoad_text_configuration() {
        $str1= "system { services {ftp; }}";
        self::$d->load_text_configuration($str1, "merge");
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));   
    }

    /**
     * @covers Device::load_set_configuration
     * @todo   Implement testLoad_set_configuration().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testLoad_set_configuration() {
        $str1= "set system services ftp";
        self::$d->load_set_configuration($str1);
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));   
     }

    /**
     * @covers Device::commit
     * @todo   Implement testCommit().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testCommit() {
        self::$d->commit();
        $comm=self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($comm,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($comm,"<ok/>"));
     }

    /**
     * @covers Device::commit_confirm
     * @todo   Implement testCommit_confirm().
     * @depends testConnect
     */
    public function testCommit_confirm() {
        self::$d->commit_confirm(40);
        $ccon=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($ccon,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($ccon,"<ok/>"));
    }

    /**
     * @covers Device::validate
     * @todo   Implement testValidate().
     * @depends testConnect
     */
    public function testValidate() {
        self::$d->validate();
        $val=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($val,"rpc-reply"));
        $this->assertGreaterThan(-1,  strpos($val,"<ok/>"));
    }
        
    /**
     * @covers Device::load_xml_file
     * @todo   Implement testLoad_xml_file().
     * @depends testConnect
     * depends testLock_Config
     */
    public function testLoad_xml_file() {
        self::$d->load_xml_file("/path/of/file");
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));
    }
    
   
     /**
     * @covers Device::load_text_file
     * @todo   Implement testLoad_text_file().
     * depends testConnect
     * depends testLoad_config
     */
    public function testLoad_text_file() {
        self::$d->load_text_file("/path/of/file");
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));
   }

    /**
     * @covers Device::load_set_file
     * @todo   Implement testLoad_set_file().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testLoad_set_file() {
        self::$d->load_set_file("path/of/file");
        $isloaded=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($isloaded,"rpc-reply"));
        $this->assertGreaterThan(-1,strpos($isloaded,"<ok/>"));
    }

    /**
     * @covers Device::get_running_config
     * @todo   Implement testGet_running_config().
     * depends testConnect
     */
    public function testGet_running_config() {
        self::$d->get_running_config();
        $run_conf=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1, strpos($run_conf,"rpc-reply"));
        $this->assertGreaterThan(-1, strpos($run_conf,"<data>"));
   }
 
    /**
     * @covers Device::unlock_config
     * @todo   Implement testUnlock_config().
     * @depends testConnect
     * @depends testLock_config
     */
    public function testUnlock_config() {
    self::$d->unlock_config();
    $isloc=  self::$d->last_rpc_reply;
    $this->assertGreaterThan(-1,strpos($isloc,"rpc-reply"));        
    $this->assertGreaterThan(-1,strpos($isloc,"<ok/>"));        
    }
       
    
    /**
     * @covers Device::open_configuration
     * @todo   Implement testOpen_configuration().
     * @depends testConnect
     * @depends testUnlock_config
     
     */
    public function testOpen_configuration() {
        self::$d->open_configuration();
        $open_conn=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($open_conn,"<error-severity>warning</error-severity>"));
     }

    /**
     * @covers Device::close_configuration
     * @todo   Implement testClose_configuration().
     * @depends testConnect
     * @depends testOpen_configuration
     */
    public function testClose_configuration() {
         self::$d->close_configuration();
         $close_conf=  self::$d->last_rpc_reply;
         $this->assertGreaterThan(-1,strpos($close_conf,"rpc-reply"));
    } 
 
    /**
     * @covers Device::close
     * @todo   Implement testClose().
     * @depends testConnect
     */
    public function testClose() {
        self::$d->close();
        $c=  self::$d->last_rpc_reply;
        $this->assertGreaterThan(-1,strpos($c,"rpc-reply"));
    }
}

?>
