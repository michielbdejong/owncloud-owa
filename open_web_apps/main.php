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

require_once 'lib/apps.php';
require_once 'lib/parser.php';

function getScope($token) {
	try {
		$stmt = OCP\DB::prepare( 'SELECT * FROM `*PREFIX*remotestorage_access` WHERE `access_token` = ?' );
		$result = $stmt->execute(array($token));
	} catch(Exception $e) {
		OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
		OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' token: '.$token, OCP\Util::DEBUG);
		return false;
	}
	$scopesFromDb = $result->fetchAll();
        $strs = array();
	foreach($scopesFromDb as $obj) {
		$strs[] = $obj['module'].':'.$obj['level'];
	}
	return implode(' ', $strs);
}
function getApps($uid) {
	try {
		$stmt = OCP\DB::prepare( 'SELECT * FROM `*PREFIX*open_web_apps` WHERE `uid_owner` = ?' );
		$result = $stmt->execute(array($uid));
	} catch(Exception $e) {
		OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
		OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' uid: '.$uid, OCP\Util::DEBUG);
		return false;
	}
	$appsFromDb = $result->fetchAll();
        $apps = array();
	foreach($appsFromDb as $app) {
		$manifest = MyApps::getManifest($app['app_id']);
 		if($manifest) {
			$origin = MyParser::idToOrigin($app['app_id']);
			$apps[$app['app_id']] = array(
				'name' => $manifest['name'],
				'launch_url' => MyParser::parseUrl($origin.$manifest['launch_path'])['clean'],
				'icon_url' => MyParser::parseUrl($origin.$manifest['icons'][128])['clean'],//in JSON this is ['128']
				'scope' => getScope($app['token']),
				'token' => $app['token']
			);
		}
	}
	return $apps;
}
function calcScopeDiv($url, $scope) {
  $existingScope = $apps[$url]['scope'];
  $newScope = parseScope($existingScope.' '.$scope);
  if($newScope['normalized'] == $existingScope) {
    return false;
  } else {
    return $newScope;
  }
}

function checkForAdd() {
  $params = array();
  $paramStrs = explode('&', $_SERVER['QUERY_STRING']);
  foreach($paramStrs as $str) {
    $parts = explode('=', $str);
    if(count($parts) == 2) {
      $params[urldecode($parts[0])] = urldecode($parts[1]);
    }
  }
  if($params['redirect_uri'] && $params['scope']) {
    $urlObj = MyParser::parse($params['redirect_uri']);
    $appId = $urlObj['protocol'].'_'.$urlObj['host'].'_'.$urlObj['port'];
    if($apps[$appId]) {
      $scopeDiff = calcScopeDiff($appId, $params['scope']);
      if($scopeDiff) {
        return array(
	  'scope_diff_id' => $appId,
          'scope_diff_add' => $scopeDiff
        );
      } else {
        return array( 'launch_app' => $appId );
      }
    } else {
      return array(
        'adding_id' => $appId,
        'adding_launch_path' => $launchPath,
        'adding_name_dirty' => $params['client_id'],
        'adding_scope' => parseScope($params['scope'])//scope.normalized and scope.human will only contain [a-zA-Z0-9%\-_\.] and spaces
      );
    }
  }
}

//...
OCP\User::checkLoggedIn();
$uid = OCP\USER::getUser();
$apps = getApps($uid);
$storage_origin = OCP\Config::getAppValue('open_web_apps',  "storage_origin", '' );
OCP\App::setActiveNavigationEntry( 'open_web_apps' );
$tmpl = new OCP\Template( 'open_web_apps', 'main', 'user' );
foreach(checkForAdd() as $k => $v) {
  $tmpl->assign($k, $v);
}
$tmpl->assign( 'user_address', $uid.'@'.$_SERVER['SERVER_NAME'] );
$tmpl->assign( 'uid', $uid );
$tmpl->assign( 'storage_origin', $storage_origin );
$tmpl->assign( 'apps', $apps );
$tmpl->printPage();
