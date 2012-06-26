<?php
/*******************************************************************************
 *
 * CoreAuthModOpenNMS.php - Authentication Module for OpenNMS
 *
 * Copyright (c) 2012 NETHINKS GmbH (Contact: Michael.Batz@nethinks.com)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ******************************************************************************/

/**
 * @author Michael Batz <Michael.Batz@nethinks.com>
 */
class CoreAuthModOpenNMS extends CoreAuthModule {

    private $username;
    private $password;
    private $passwordHash;

    public function __construct() 
    {
	parent::$aFeatures = Array(
            // General functions for authentication
            'passCredentials' => true,
            'getCredentials' => true,
            'isAuthenticated' => true,
            'getUser' => true,
            'getUserId' => true,

            // Changing passwords
            'passNewPassword' => false,
            'changePassword'  => false,
            'resetPassword'   => false,

            // Managing users
            'createUser' => false,
        );   
    }

    public function passCredentials($aData)
    {
	if(isset($aData['user']))
	{
		$this->username = $aData['user'];
	}

	if(isset($aData['password']))
	{
		$this->password = $aData['password'];
		$this->passwordHash = $this->hashPassword($aData['password']);
	}
	if(isset($aData['passwordHash']))
	{
		$this->passwordHash = $aData['passwordHash'];
	}
    }

    private function hashPassword($password)
    {
	return md5($password);
    }

    public function passNewPassword($aData)
    {
	//not implemented
    }

    public function changePassword()
    {
	//not implemented
    }

    public function getCredentials()
    {
        return Array('user' => $this->username,
                     'passwordHash' => $this->passwordHash,
                     'userId' => $this->username);
    }

    public function isAuthenticated()
    {
	//if no username is set, return false
	if($this->username == "")
	{
		return false;
	}

	//define variables
	$readUsername = "";
	$readPasswordHash = "";
	$authSuccess = FALSE;

	// prepare XMLReader
	$passwdfile = "file:///etc/opennms/users.xml";
	$xml = new XMLReader();
	$xml->open($passwdfile);


	while($xml->read() == true)
	{
		//read user object in xml file
		if($xml->name == "user")
		{
			//get user-id and password hash of user object
			$innerxml = new XMLReader();
			$innerxml->xml($xml->readOuterXML());
			while($innerxml->read() == true)
			{
				//read user-id
				if($innerxml->name == "user-id" && $readUsername == "")
				{
					//get password and compare to password given hash
					$readUsername = $innerxml->readString();
				}
				//read password hash
				if($innerxml->name == "password" && $readPasswordHash == "")
				{
					//get password and compare to password given hash
					$readPasswordHash = $innerxml->readString();
				}
			}
			//compare read username and password with given login data
			if(strcasecmp($readUsername, $this->username) == 0  && strcasecmp($readPasswordHash, $this->passwordHash) == 0)
			{
				//user authenticated
				$authSuccess = TRUE;
			}
			$innerxml->close();
		}
		$readUsername = "";
		$readPasswordHash = "";
	}
	$xml->close();

	//if no data has matched with given login data, user not authenticated
	return $authSuccess;
    }

    public function getUser()
    {
	return $this->username;
    }

    public function getUserId()
    {

	return $this->username;
    }
}
?>
