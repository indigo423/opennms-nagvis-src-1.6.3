<?php
/*****************************************************************************
 *
 * opennms_redirect.php - Redirects the requests to OpenNMS WebUI
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
//get nodeid from NagVis Hostname
$nodeid = substr($_GET['host'], strpos($_GET['host'], "@") + 1 ); 

//properties for OpenNMS
$opennmsProtocol = "http://";
$opennmsHost = $_SERVER['SERVER_NAME'];
$opennmsPort = 8980;
$url = $opennmsProtocol.$opennmsHost.":".$opennmsPort."/opennms/element/node.jsp?node=$nodeid";

//do redirect
header("HTTP/1.1 301 Moved Permanently");
header("Location: $url");
?>
