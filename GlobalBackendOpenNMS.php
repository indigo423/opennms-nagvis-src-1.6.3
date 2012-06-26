<?php
/*****************************************************************************
 *
 * GlobalBackendOpenNMS.php - OpenNMS Backend for NagVis
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
 *****************************************************************************/

/**
 * @author	Michael Batz <Michael.Batz@nethinks.com>
 */

class GlobalBackendOpenNMS {

	private $CORE;
	private $dbHost;
	private $dbName;
	private $dbPort;
	private $dbUser;
	private $dbPassword;

    /**
    * Read the configuration and set up variables
    */
    public function __construct($CORE, $backendId)
    {
	$this->CORE = $CORE;
	$this->backendId = $backendId;

	$this->dbName = cfg('backend_'.$backendId, 'dbname');
        $this->dbUser = cfg('backend_'.$backendId, 'dbuser');
        $this->dbPassword = cfg('backend_'.$backendId, 'dbpass');
        $this->dbHost = cfg('backend_'.$backendId, 'dbhost');
        $this->dbPort = cfg('backend_'.$backendId, 'dbport');

    }

    /**
     * Static function which returns the backend specific configuration options
     * and defines the default values for the options
     */
    public static function getValidConfig()
    {
        return Array('dbhost' => Array('must' => 1,
            'editable' => 1,
            'default' => 'localhost',
            'match' => MATCH_STRING_NO_SPACE),
        'dbport' => Array('must' => 1,
            'editable' => 1,
            'default' => '5432',
            'match' => MATCH_INTEGER),
        'dbname' => Array('must' => 1,
            'editable' => 1,
            'default' => 'opennms',
            'match' => MATCH_STRING_NO_SPACE),
        'dbuser' => Array('must' => 1,
            'editable' => 1,
            'default' => 'opennms',
            'match' => MATCH_STRING_NO_SPACE),
        'dbpass' => Array('must' => 1,
            'editable' => 1,
            'default' => 'opennms',
            'match' => MATCH_STRING_EMPTY));
    }

    /**
     * Used in WUI forms to populate the object lists when adding or modifying
     * objects in WUI.
     */
    public function getObjects($type, $name1Pattern = '', $name2Pattern = '')
    {
	$output = Array();
	switch($type)
	{
		//object is a host
		case "host":
			$dbConnection = pg_connect("host=$this->dbHost port=$this->dbPort dbname=$this->dbName user=$this->dbUser password=$this->dbPassword");
			//get all nodes from OpenNMS database
			$dbResult = pg_query($dbConnection, "SELECT nodelabel,nodeid FROM node ORDER BY nodelabel");
			while($dbResultRow = pg_fetch_array($dbResult))
			{
				//output with following pattern: nodelabel@nodeid
				$output[] = Array('name1' => "$dbResultRow[0]@$dbResultRow[1]", 'name2' => $dbResultRow[0]);
			}
			pg_free_result($dbResult);
			break;

		//object is a service
		case "service":	
			$dbConnection = pg_connect("host=$this->dbHost port=$this->dbPort dbname=$this->dbName user=$this->dbUser password=$this->dbPassword");
			//get nodeid of current node
			$nodeId = substr($name1Pattern, strpos($name1Pattern, "@") + 1 );
			//get all services of the current node from OpenNMS database
			$dbResult = pg_query($dbConnection, "SELECT servicename, ipaddr FROM ifservices, service WHERE ifservices.serviceid = service.serviceid AND nodeid = $nodeId ORDER BY servicename, ipaddr");
			while($dbResultRow = pg_fetch_array($dbResult))
			{
				//output with following pattern: servicename@IP
				$output[] = Array('name1' => "$dbResultRow[0]@$dbResultRow[1]", 'name2' => "$dbResultRow[0]@$dbResultRow[1]");
			}
			pg_free_result($dbResult);
			break;
	}
	return $output;
    }

    /**
     * Returns the state with detailed information of a list of hosts. Using the
     * given objects and filters.
     */
    public function getHostState($objects, $options, $filters)
    {
	$output = Array();
	if(count($filters) == 1 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '=') 
	{
		$dbConnection = pg_connect("host=$this->dbHost port=$this->dbPort dbname=$this->dbName user=$this->dbUser password=$this->dbPassword");
		//walk through all objects of the collection
		foreach($objects as $object)
		{
			$objectName = $object[0]->getName();
			if(strpos($objectName, '@') == FALSE)
			{
				break;
			}
			//get nodeID
			$objectId = substr($objectName, strpos($objectName, "@") + 1 );
			//check, if node is in OpenNMS
			$dbResult = pg_query($dbConnection, "SELECT count(*) FROM node WHERE nodeid=$objectId");
			if($dbResult != FALSE)
			{
				$dbResultRow = pg_fetch_array($dbResult);
				if($dbResultRow[0] == 0)
				{
					//return unknown
					$output[$objectName] = Array(	'alias' => $objectName, 
									'state' => "UNKNOWN",
									'output' => "Object not found in OpenNMS",
									'display_name' => $objectName,
									'address' => $objectName,
									'notes' => "",
									'last_check' => 0,
									'next_check' => 0,
									'current_check_attempt' => 1,
									'max_check_attempts' => 1,
									'last_state_change' => 0,
									'last_hard_state_change' => 0,
									'statusmap_image' => "",
									'perfdata' => "",
									'problem_has_been_acknowledged' => 0,
									'in_downtime' => 0);

				}
				else
				{
					//get all current outages of the node
					$dbResult = pg_query($dbConnection, "SELECT count(*) FROM outages WHERE nodeid=$objectId AND ifregainedservice is null");
					if($dbResult != FALSE)
					{
						$dbResultRow = pg_fetch_array($dbResult);
						//if there is an outage
						if($dbResultRow[0] > 0)
						{
							//return down
							$output[$objectName] = Array(	'alias' => $objectName, 
											'state' => "DOWN",
											'output' => "Some or all services on this host are down",
											'display_name' => $objectName,
											'address' => $objectName,
											'notes' => "",
											'last_check' => 0,
											'next_check' => 0,
											'current_check_attempt' => 1,
											'max_check_attempts' => 1,
											'last_state_change' => 0,
											'last_hard_state_change' => 0,
											'statusmap_image' => "",
											'perfdata' => "",
											'problem_has_been_acknowledged' => 0,
											'in_downtime' => 0);
						}
						//if there is no outage
						else
						{
							//return UP
							$output[$objectName] = Array(	'alias' => $objectName, 
											'state' => "UP",
											'output' => "All services of this host are up",
											'display_name' => $objectName,
											'address' => $objectName,
											'notes' => "",
											'last_check' => 0,
											'next_check' => 0,
											'current_check_attempt' => 1,
											'max_check_attempts' => 1,
											'last_state_change' => 0,
											'last_hard_state_change' => 0,
											'statusmap_image' => "",
											'perfdata' => "",
											'problem_has_been_acknowledged' => 0,
											'in_downtime' => 0);
			
						}
					}

				}
			}

		}
	}
	return $output;
    }

    /**
     * Returns the state with detailed information of a list of services. Using
     * the given objects and filters.
     */
    public function getServiceState($objects, $options, $filters)
    {
	$output = Array();
	if(count($filters) == 2 && $filters[0]['key'] == 'host_name' && $filters[0]['op'] == '='
            && $filters[1]['key'] == 'service_description' && $filters[1]['op'] == '=')
	{
		$dbConnection = pg_connect("host=$this->dbHost port=$this->dbPort dbname=$this->dbName user=$this->dbUser password=$this->dbPassword");
		//walk through all objects of the collection
		foreach($objects as $object)
		{
			$objectName = $object[0]->getName();
			//get nodeID
			$objectId = substr($objectName, strpos($objectName, "@") + 1 );
			$objectService = $object[0]->getServiceDescription();
			//get service name
			$objectServiceName = substr($objectService, 0, strpos($objectService, "@"));
			//get service IP
			$objectServiceIP = substr($objectService, strpos($objectService, "@") + 1 );
			//check if the node and service is in OpenNMS
			$dbResult = pg_query($dbConnection, "SELECT count(*) FROM ifservices, service WHERE ifservices.serviceid = service.serviceid AND nodeid=$objectId AND ipaddr='$objectServiceIP' AND servicename='$objectServiceName'");
			if($dbResult != FALSE)
			{
				$dbResultRow = pg_fetch_array($dbResult);
				if($dbResultRow[0] == 0)
				{
					//return service UNKNOWN
					$output[$objectName.'~~'.$objectService] =  Array(	'name' => $objectServiceName,
												'service_description' => $objectService,
												'alias' => $objectServiceName, 
												'state' => "UNKNOWN",
												'output' => "Service not found in OpenNMS",
												'display_name' => $objectServiceName,
												'address' => $objectServiceName,
												'notes' => "",
												'last_check' => 0,
												'next_check' => 0,
												'current_check_attempt' => 1,
												'max_check_attempts' => 1,
												'last_state_change' => 0,
												'last_hard_state_change' => 0,
												'statusmap_image' => "",
												'perfdata' => "",
												'problem_has_been_acknowledged' => 0,
												'in_downtime' => 0);

				}
				else
				{
					//get all current outages of this service
					$dbResult = pg_query($dbConnection, "SELECT count(*) FROM outages,service WHERE outages.serviceid = service.serviceid AND nodeid=$objectId AND ipaddr='$objectServiceIP' AND servicename='$objectServiceName' AND ifregainedservice is null");
					if($dbResult != FALSE)
					{
						$dbResultRow = pg_fetch_array($dbResult);
						//if there are outages for this service
						if($dbResultRow[0] > 0)
						{
							//return service DOWN
							$output[$objectName.'~~'.$objectService] =  Array(	'name' => $objectServiceName,
														'service_description' => $objectService,
														'alias' => $objectServiceName, 
														'state' => "DOWN",
														'output' => "Service is down",
														'display_name' => $objectServiceName,
														'address' => $objectServiceName,
														'notes' => "",
														'last_check' => 0,
														'next_check' => 0,
														'current_check_attempt' => 1,
														'max_check_attempts' => 1,
														'last_state_change' => 0,
														'last_hard_state_change' => 0,
														'statusmap_image' => "",
														'perfdata' => "",
														'problem_has_been_acknowledged' => 0,
														'in_downtime' => 0);
		
						}
						else
						{
							//return service UP
							$output[$objectName.'~~'.$objectService] =  Array(	'name' => $objectServiceName,
														'service_description' => $objectService,
														'alias' => $objectServiceName, 
														'state' => "UP",
														'output' => "Service is up",
														'display_name' => $objectServiceName,
														'address' => $objectServiceName,
														'notes' => "",
														'last_check' => 0,
														'next_check' => 0,
														'current_check_attempt' => 1,
														'max_check_attempts' => 1,
														'last_state_change' => 0,
														'last_hard_state_change' => 0,
														'statusmap_image' => "",
														'perfdata' => "",
														'problem_has_been_acknowledged' => 0,
														'in_downtime' => 0);
						}		

					}		

				}
				
			}

		}
	}
	return $output;
    }

    /**
     * Returns the service state counts for a list of hosts. Using
     * the given objects and filters.
     */
    public function getHostStateCounts($objects, $options, $filters)
    {
	//not implemented
	return Array();
    }

    /**
     * Returns the host and service state counts for a list of hostgroups. Using
     * the given objects and filters.
     */
    public function getHostgroupStateCounts($objects, $options, $filters)
    {
	//not implemented
	return Array();
    }

    /**
     * Returns the service state counts for a list of servicegroups. Using
     * the given objects and filters.
     */
    public function getServicegroupStateCounts($objects, $options, $filters)
    {
	//not implemented
	return Array();
    }

    /**
     * Returns a list of host names which have no parent defined.
     */
    public function getHostNamesWithNoParent()
    {
	//not implemented
	return Array();
    }

    /**
     * Returns a list of host names which are direct childs of the given host
     */
    public function getDirectChildNamesByHostName($hostName)
    {
	//not implemented
	return Array();
    }

    /**
     * Returns a list of host names which are direct parents of the given host
     */
    public function getDirectParentNamesByHostName($hostName)
    {
	//not implemented
	return Array();
    }

}
?>
