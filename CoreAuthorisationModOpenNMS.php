<?php
/*******************************************************************************
 *
 * CoreAuthorisationModOpenNMS.php - Authorsiation module based on OpenNMS
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
class CoreAuthorisationModOpenNMS extends CoreAuthorisationModule {

    private $permissions;
    private $roles;

    public function __construct() 
    {
	$this->permissions = Array(Array(Array(Array())));
	
	//no permissions (only logout)
	$this->permissions[0]['Auth']['logout']['*'] = Array();
	$this->permissions[0]['Overview']['view']['*'] = Array();

 	//permissions for admin users
	$this->permissions[1]['Auth']['*']['*'] = Array();
	$this->permissions[1]['AutoMap']['*']['*'] = Array();
	$this->permissions[1]['General']['*']['*'] = Array();
	$this->permissions[1]['MainCfg']['*']['*'] = Array();
	$this->permissions[1]['Map']['*']['*'] = Array();
	$this->permissions[1]['Overview']['*']['*'] = Array();
	$this->permissions[1]['Rotation']['*']['*'] = Array();
	$this->permissions[1]['Search']['*']['*'] = Array();
	$this->permissions[1]['User']['setOption']['*'] = Array();
	$this->permissions[1]['ManageBackgrounds']['*']['*'] = Array();
	$this->permissions[1]['ManageShapes']['*']['*'] = Array();

	//permissions for users
	$this->permissions[2]['Auth']['logout']['*'] = Array();
	$this->permissions[2]['AutoMap']['view']['*'] = Array();
	$this->permissions[2]['General']['*']['*'] = Array();
	$this->permissions[2]['Map']['view']['*'] = Array();
	$this->permissions[2]['Overview']['view']['*'] = Array();
	$this->permissions[2]['Rotation']['view']['*'] = Array();
	$this->permissions[2]['Search']['view']['*'] = Array();
	$this->permissions[2]['User']['setOption']['*'] = Array();

	//all user roles
	$this->roles = Array();
	$this->roles[1] = "Admin";
	$this->roles[2] = "User";
    }

    public function deletePermission($mod, $name) 
    {
	//not implemented
    }

    public function createPermission($mod, $name) 
    {
	//not implemented
    }

    public function deleteRole($roleId) 
    { 
	//not implemented
    }

    public function deleteUser($userId) 
    {
	//not implemented
    }

    public function updateUserRoles($userId, $roles) 
    {
	//not implemented
    }

    public function getUserRoles($userId) 
    {
	$isadmin = false;
	$roles = Array();

	//read from /etc/opennms/magic-users.properties	
	$file = fopen("/etc/opennms/magic-users.properties", "r");
	if($file != false)
	{
		//walking through all the lines of that file
		while($line = fgets($file))
		{
			//if string role.admin.users= is found, try to match given username
			if(strncasecmp("role.admin.users=", $line, 17) == 0)
			{
				if(preg_match("/\b$userId\b/", substr_replace($line, "", 0, 17)) > 0)
				{
					$isadmin = true;
				}
			}
		}
		fclose($file);
	}

	if($isadmin)
	{
		$roles[1] = $this->roles[1];
	}
	else
	{
		$roles[2] = $this->roles[2];
	}

	return $roles;
    }

    public function getAllRoles() 
    {
	return $this->roles;
    }

    public function getRoleId($sRole) 
    {
	$roleid = 0;
	$searchresult = array_search($sRole, $this->roles);
	if($searchresult > 0)
	{
		$roleid = $searchresult;
	}
	return $roleid;
    }

    public function getAllPerms() 
    {
	//not implemented
    }

    public function getRolePerms($roleId) 
    {
	//not implemented
    }

    public function updateRolePerms($roleId, $perms) 
    {
	//not implemented
    }

    public function checkRoleExists($name) 
    {
	//not implemented
    }

    public function createRole($name) 
    {
	//not implemented
    }

    public function parsePermissions() 
    {
	//get current username
	global $AUTH;
	$username = $AUTH->getUser();

	//get all user roles of the current user
	$roles = $this->getUserRoles($username);

	//get roleid of the current user
	$roleid = 0;
	foreach($roles as $role)
	{	
		$roleid = $this->getRoleId($role);
	}
	
	//return permissions of the current user
	return $this->permissions[$roleid];
    }

    private function checkUserExistsById($id) 
    {
	//not implemented
    }

    public function getUserId($sUsername) 
    {
	return $sUsername;
    }
}
?>
