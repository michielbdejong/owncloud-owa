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
function getApps() {
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
		$ret = MyStorage::get($uid, $app['manifest_path']);
		$manifest;
		try {
			$manifest = json_decode($ret['content'], true);
		} catch(Exception $e) {
		}
 		if($manifest) {
			$launchUrl = $manifest['origin'].$manifest['launch_path'];
			$apps[$launchUrl] = array(
				'name' => $manifest['name'],
				'icon' => $manifest['icons']['128'],
				'scope' => getScope($app['token']),
				'token' => $app['token']
			);
		}
	}
	return $apps;
}

function toHuman($map) {
  $items = array();
  foreach($map as $module => $level) {
    if($module == 'root') {
      $thing = 'everything';
    } else {
      $thing = 'your '.$module;
    }
    if($level == 'r') {
      $items[] = 'read-only access to '.$thing;
    } else {
      $items[] = 'full access to '.$thing;
    }
  }
  if(count($items) == 0) {
    return 'no access to anything';
  } else if(count($items) == 1) {
    return $items[0];
  } else if(count($items) == 2) {
    return $items[0].' and '.$items[1];
  } else {
    $str = '';
    for($i = 0; $i<count($items)-1; $i++) {
      $str .= $items[$i].', ';
    }
    return $str.' and '.$items[count($items)-1];
  }
}
function parseRedirectUri($dirty) {
  $parts = explode('/', $dirty);
  if(count($parts)<4) {
    return array(null, null);
  }
  if($parts[0] == 'http:') {
    $protocol = 'http';
  } else if($parts[0] == 'https:') {
    $protocol = 'https';
  } else {
    return array(null, null);
  }
  if($parts[1] != '') {
    return array(null, null);
  }
  $hostParts = explode(':', $parts[2]);
  $hostName = ereg_replace('[^a-zA-Z0-9\-\.]', '', $hostParts[0]);
  $hostPort = ereg_replace('[^0-9]', '', $hostParts[1]);
  return array(
    $protocol.'_'.$hostName.'_'.$hostPort,
    '/'.ereg_replace('[<\']', '', implode('/', array_slice($parts, 3)))
  );
}
function parseScope($scope) {
  $map = array();
  $parts = explode(' ', $scope);
  foreach($parts as $str) {
    $moduleAndLevel = explode(':', $str);
    if(count($moduleAndLevel)==2 && in_array($moduleAndLevel[1], array('r', 'rw'))) {
      //https://tools.ietf.org/id/draft-dejong-remotestorage-00.txt, section 4:
      //Item names MAY contain a-z, A-Z, 0-9, %,  -, _.
      //Note: we should allow '.' too in remotestorage-01.
      //Allowing it here as an intentional violation:
      $moduleName = ereg_replace('[^a-zA-Z0-9%\-_\.]', '', $moduleAndLevel[0]); 
      if(strlen($moduleName)>0 && $map[$moduleName] != 'rw') {//take the strongest one
        $map[$moduleName] = $moduleAndLevel[1];
      }
    }
  }
  //root:rw is almighty and cannot coexist with other scopes:
  if($map['root'] == 'rw') {
    $map = array('root' => 'rw');
  }
  //root:r cannot coexist with other 'r' scopes:
  if($map['root'] == 'r') {
    foreach($map as $module => $level) {
      if($module != 'root' && $level == 'r') {
        unset($map[$module]);
      }
    }
  }
  $reassembleParts = array();
  foreach($map as $module => $level) {
    $reassembleParts[] = $module.':'.$level;
  }
  sort($reassembleParts);
  return array(
    'map' => $map,
    'normalized' => implode(' ', $reassembleParts),
    'human' => toHuman($map)
  );
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
    list($origin, $launchPath) = parseRedirectUri($params['redirect_uri']);
    if($apps[$origin]) {
      $scopeDiff = calcScopeDiff($origin, $params['scope']);
      if($scopeDiff) {
        return array(
	  'scope_diff_origin' => $origin,
          'scope_diff_add' => $scopeDiff
        );
      } else {
        return array( 'launch_app' => $origin );
      }
    } else {
      return array(
        'adding_origin' => $origin,
        'adding_launch_path' => $launchPath,
        'adding_name_dirty' => $params['client_id'],
        'adding_scope' => parseScope($params['scope'])//scope.normalized and scope.human will only contain [a-zA-Z0-9%\-_\.] and spaces
      );
    }
  }
}

//...
$apps = getApps();
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
