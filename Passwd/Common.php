<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Michael Wallner <mike@iworks.at>                             |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once('System.php');

/**
* Baseclass for File_Passwd_* classes.
* 
* <kbd><u>
*   Provides basic operations:
* </u></kbd>
*   o opening & closing
*   o locking & unlocking
*   o loading & saving
*   o check if user exist
*   o delete a certain user
*   o list users
* 
* @author   Michael Wallner <mike@iworks.at>
* @package  File_Passwd
* @version  $Revision$
* @access   protected
* @internal extend this class for your File_Passwd_* class
*/
class File_Passwd_Common {

    /**
    * passwd file
    *
    * @var string
    * @access protected
    */
    var $_file = 'passwd';
    
    /**
    * File content
    *
    * @var aray
    * @access protected
    */
    var $_contents = array();
    
    /**
    * Users
    *
    * @var array
    * @access protected
    */
    var $_users = array();
    
    /**
    * Constructor (ZE2)
    *
    * @access protected
    * @param  string    $file   path to passwd file
    */
    function __construct($file = 'passwd'){
        $this->setFile($file);
    }
    
    /**
    * Get Copy of this object (ZE2)
    *
    * @access public
    * @return object    copy of this opject
    */
    function __clone(){
        return $this;
    }
    
    /**
    * Parse the content of the file
    *
    * You must overwrite this method in your File_Passwd_* class.
    * 
    * @abstract
    * @internal
    * @access public
    * @return object    PEAR_Error
    */
    function parse(){
        return PEAR::raiseError('Method parse() was not implemented!');
    }
    
    /**
    * Apply changes and rewrite passwd file
    *
    * You must overwrite this method in your File_Passwd_* class.
    * 
    * @abstract
    * @internal
    * @access public
    * @return object    PEAR_Error
    */
    function save(){
        return PEAR::raiseError('Method save() was not implemented!');
    }
    
    /**
    * Opens a file, locks it exclusively and returns the filehandle
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in the desired mode
    *   o file couldn't be locked exclusively
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed resource of type file handle or PEAR_Error
    * @param  string    $mode   the mode to open the file with
    */
    function &_open($mode){
        $file   = realpath($this->_file);
        $dir    = dirname($file);
        if (!is_dir($dir) && !System::mkDir('-p -m 0755 ' . $dir)) {
            return PEAR::raiseError(
                'Couldn\'t create Directory in which the ' .
                "passwd file '$file' should reside."
            );
        }
        if (!is_resource($fh = @fopen($file, $mode))) {
            return PEAR::raiseError("Couldn't open '$file' with mode '$mode'.");
        }
        if (!@flock($fh, LOCK_EX)) {
            fclose($fh);
            return PEAR::raiseError("Couldn't lock file '$file'.");
        }
        return $fh;
    }
    
    /**
    * Closes a prior opened and locked file handle
    *
    * Returns a PEAR_Error if:
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed true on success or PEAR_Error
    * @param  resource  $file_handle    the file handle to operate on
    */
    function _close(&$file_handle){
        if (!@flock($file_handle, LOCK_UN)) {
            return PEAR::raiseError('Couldn\'t release lock from filehandle.');
        }
        if (!@fclose($file_handle)) {
            return PEAR::raiseError('Couldn\'t close filehandle.');
        }
        return true;
    }
    
    /**
    * Loads the file
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in read mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function load(){
        $fh = &$this->_open('r');
        if (PEAR::isError($fh)) {
            return $fh;
        }
        $this->_contents = array();
        while ($line = fgets($fh)){
            $line = trim(preg_replace('/^(\S*.*)#.*$/', '\\1', $line));
            if (empty($line)) {
                continue;
            }
            $this->_contents[] = $line;
        }
        $e = $this->_close($fh);
        if (PEAR::isError($e)) {
            return $e;
        }
        return $this->parse();
    }
    
    /**
    * Save the modified content to the passwd file
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in write mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed true on success or PEAR_Error
    */
    function _save($content){
        $fh = &$this->_open('w');
        if (PEAR::isError($fh)) {
            return $fh;
        }
        fputs($fh, $content);
        return $this->_close($fh);
    }
    
    /**
    * Set path to passwd file
    *
    * @access public
    * @return void
    */
    function setFile($file){
        $this->_file = $file;
    }
    
    /**
    * Get path of passwd file
    *
    * @access public
    * @return string
    */
    function getFile(){
        return $this->_file;
    }

    /**
    * Check if a certain user already exists
    *
    * @access public
    * @return bool
    * @param  string    $user   the name of the user to check if already exists
    */
    function userExists($user){
        return isset($this->_users[$user]);
    }
    
    /**
    * Delete a certain user
    *
    * Returns a PEAR_Error if user doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param  string    
    */
    function delUser($user){
        if (!$this->userExists($user)) {
            return PEAR::raiseError("User '$user' doesn't exist.");
        }
        unset($this->_users[$user]);
        return true;
    }
    
    /**
    * List user
    *
    * Returns a PEAR_Error if <var>$user</var> doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed array of a/all user(s) or PEAR_Error
    * @param  string    $user   the user to list or all users if empty
    */
    function listUser($user = ''){
        if (empty($user)) {
            return $this->_users;
        }
        if (!$this->userExists($user)) {
            return PEAR::raiseError("User '$user' doesn't exist.");
        }
        return $this->_users[$user];
    }

}
?>