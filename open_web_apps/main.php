<?php

/**
* ownCloud - Unhosted apps Example
*
* @author Frank Karlitschek
* @author Florian Hülsmann
* @copyright 2011 Frank Karlitschek karlitschek@kde.org
* @copyright 2012 Florian Hülsmann fh@cbix.de
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

require_once 'lib/storage.php';

// Check if we are a user
OCP\User::checkLoggedIn();

//fetch the list of apps:
$uid = OCP\USER::getUser();
try {
	$stmt = OCP\DB::prepare( 'SELECT * FROM `*PREFIX*open_web_apps` WHERE `uid_owner` = ?' );
	$result = $stmt->execute(array($uid));
} catch(Exception $e) {
	OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
	OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' uid: '.$uid, OCP\Util::DEBUG);
	return false;
}
$apps = $result->fetchAll();
for($i=0; $i<count($apps); $i++) {
  $obj = MyStorage::get($uid, $apps[$i]['manifest_path']);
  try {
    $apps[$i]['manifest'] = json_decode($obj['content'], true);
  } catch(Exception $e) {
  }
}
$storage_origin = OCP\Config::getAppValue('open_web_apps',  "storage_origin", '' );
OCP\App::setActiveNavigationEntry( 'open_web_apps' );
//OCP\Util::addScript( "open_web_apps", "helpers" );
$tmpl = new OCP\Template( 'open_web_apps', 'main', 'user' );
$tmpl->assign( 'uid', $uid );
$tmpl->assign( 'storage_origin', $storage_origin );
$tmpl->assign( 'apps', $apps );
$tmpl->printPage();
