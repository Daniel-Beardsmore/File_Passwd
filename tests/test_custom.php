<?php
require_once 'System.php';
require_once 'PHPUnit.php';
require_once 'File/Passwd/Custom.php';


$GLOBALS['tmpfile'] = System::mktemp();
$GLOBALS['map']     = array(
    'extra1', 'extra2', 'extra3'
);
$GLOBALS['users']   = array(
    'mike' => array(
        'pass' =>   'mikespass',
    ),
    'pete' => array(
        'pass' =>   'petespass',
    ),
    'mary' => array(
        'pass' =>   'maryspass',
    )
);


/**
 * TestCase for File_Passwd_CustomTest class
 * Generated by PHPEdit.XUnit Plugin
 * 
 */
class File_Passwd_CustomTest extends PHPUnit_TestCase{

    var $pwd;
    
    /**
     * Constructor
     * @param string $name The name of the test.
     * @access protected
     */
    function File_Passwd_CustomTest($name){
        $this->PHPUnit_TestCase($name);
    }
    
    /**
     * Called before the test functions will be executed this function is defined in PHPUnit_TestCase and overwritten here
     * @access protected
     */
    function setUp(){
        $this->pwd = &new File_Passwd_Custom();
    }
    
    /**
     * Called after the test functions are executed this function is defined in PHPUnit_TestCase and overwritten here
     * @access protected
     */
    function tearDown(){
        $this->pwd = null;
    }
    
    /**
     * Regression test for File_Passwd_Custom.setDelim method
     * @access public
     */
    function testsetDelim(){
        $this->pwd->setDelim('abc');
        $this->assertEquals('a', $this->pwd->getDelim());
    }
    
    /**
     * Regression test for File_Passwd_Custom.getDelim method
     * @access public
     */
    function testgetDelim(){
        $this->pwd->setDelim('%');
        $this->assertTrue('%', $this->pwd->getDelim());
    }
    
    /**
     * Regression test for File_Passwd_Custom.setEncFunc method
     * @access public
     */
    function testsetEncFunc(){
        $this->assertTrue(PEAR::isError($this->pwd->setEncFunc('nonexistant')));
        $this->assertFalse(PEAR::isError($this->pwd->setEncFunc('md5')));
    }
    
    /**
     * Regression test for File_Passwd_Custom.getEncFunc method
     * @access public
     */
    function testgetEncFunc(){
        $this->pwd->setEncFunc(array('File_Passwd', 'crypt_plain'));
        $this->assertEquals('File_Passwd::crypt_plain', $this->pwd->getEncFunc());
    }
    
    /**
     * Regression test for File_Passwd_Custom.useMap method
     * @access public
     */
    function testuseMap(){
        $this->pwd->useMap(false);
        $this->assertFalse($this->pwd->useMap());
        $this->pwd->useMap(true);
        $this->assertTrue($this->pwd->useMap());
    }
    
    /**
     * Regression test for File_Passwd_Custom.setMap method
     * @access public
     */
    function testsetMap(){
        $this->pwd->setMap($GLOBALS['map']);
        $this->assertEquals($GLOBALS['map'], $this->pwd->getMap());
    }
    
    /**
     * Regression test for File_Passwd_Custom.getMap method
     * @access public
     */
    function testgetMap(){
        $this->pwd->setMap();
        $this->assertEquals(array(), $this->pwd->getMap());
    }
    
    /**
     * Regression test for File_Passwd_Custom.save method
     * @access public
     */
    function testsave(){
        $this->pwd->setFile($GLOBALS['tmpfile']);
        $this->pwd->setDelim('|');
        $this->pwd->setEncFunc(array('File_Passwd', 'crypt_plain'));
        foreach ($GLOBALS['users'] as $user => $pass_r) {
            $this->pwd->addUser($user, $pass_r['pass']);
        }
        $this->assertFalse(PEAR::isError($this->pwd->save()));
        $this->assertEquals(file('passwd.custom.txt'), file($GLOBALS['tmpfile']));
    }
    
    /**
     * Regression test for File_Passwd_Custom.parse method
     * @access public
     */
    function testparse(){
        $this->pwd->useMap(true);
        $this->pwd->setFile('passwd.custom.txt');
        $this->pwd->setDelim('|');
        $this->pwd->load();
        $this->assertEquals($GLOBALS['users'], $this->pwd->_users);
    }
    
    /**
     * Regression test for File_Passwd_Custom.addUser method
     * @access public
     */
    function testaddUser(){
        $this->pwd->useMap(true);
        $this->pwd->setEncFunc('md5');
        $this->pwd->addUser('testadd', 'pass');
        $this->assertTrue($this->pwd->userExists('testadd'));
        $this->assertEquals(md5('pass'), $this->pwd->_users['testadd']['pass']);
    }
    
    /**
     * Regression test for File_Passwd_Custom.modUser method
     * @access public
     */
    function testmodUser(){
        $this->pwd->useMap(true);
        $this->pwd->setEncFunc('md5');
        $this->pwd->addUser('testmod', 'pass');
        $this->assertFalse(PEAR::isError($this->pwd->modUser('testmod', array('pass' => 'newpass'))));
        $this->assertEquals('newpass', $this->pwd->_users['testmod']['pass']);
    }
    
    /**
     * Regression test for File_Passwd_Custom.changePasswd method
     * @access public
     */
    function testchangePasswd(){
        $this->pwd->useMap(true);
        $this->pwd->setEncFunc('md5');
        $this->pwd->addUser('changepass', 'pass');
        $this->assertFalse(PEAR::isError($this->pwd->changePasswd('changepass', 'newpass')));
        $this->assertEquals(md5('newpass'), $this->pwd->_users['changepass']['pass']);
    }
    
    /**
     * Regression test for File_Passwd_Custom.verifyPasswd method
     * @access public
     */
    function testverifyPasswd(){
        $this->pwd->addUser('testverify', 'password');
        $this->assertFalse(PEAR::isError($this->pwd->verifyPasswd('testverify', 'password')));
    }

    function teststaticAuth(){
        $this->assertTrue(true === File_Passwd::staticAuth('Custom', 'passwd.custom.txt', 'mike', 'mikespass', array(array('File_Passwd', 'crypt_plain'), '|')));
        $this->assertTrue(false === File_Passwd::staticAuth('Custom', 'passwd.custom.txt', 'mike', 'abc', array(array('File_Passwd', 'crypt_plain'), '|')));
        $this->assertTrue(PEAR::isError(File_Passwd::staticAuth('Custom', 'passwd.custom.txt', 'mike', 'mikespass')));
    }
    
}

?>