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
$appsFromDb = $result->fetchAll();
$apps = array();
for($i=0; $i<count($appsFromDb); $i++) {
  $obj = MyStorage::get($uid, $appsFromDb[$i]['manifest_path']);
  $manifest;
  try {
    $manifest = json_decode($obj['content'], true);
  } catch(Exception $e) {
  }
  if($manifest) {
    $launchUrl = $manifest['origin'].$manifest['launch_path'];
    $apps[$launchUrl] = array(
      'name' => $manifest['name'],
      'icon' => $manifest['icons']['128'],
      'scope' => $appsFromDb[$i]['scope'],
      'token' => $appsFromDb[$i]['token']
    );
  }
}
$storage_origin = OCP\Config::getAppValue('open_web_apps',  "storage_origin", '' );
OCP\App::setActiveNavigationEntry( 'open_web_apps' );
//OCP\Util::addScript( "open_web_apps", "helpers" );

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
    if($apps[$params['redirect_uri']]) {
      $scopeDiff = calcScopeDiff($params['redirect_uri'], $params['scope']);
      if($scopeDiff) {
        $tmpl->assign( 'scope_diff_app', $params['redirect_uri'] );
        $tmpl->assign( 'scope_diff_add', $scopeDiff );
      } else {
        $tmpl->assign( 'launch_app', $params['redirect_uri'] );
      }
    } else {
      $tmpl->assign( 'adding_app', $params['redirect_uri'] );
      $tmpl->assign( 'adding_name', $params['client_id'] );
      $tmpl->assign( 'adding_scope', parseScope($params['scope']) );
    }
  }
}
$tmpl = new OCP\Template( 'open_web_apps', 'main', 'user' );
$tmpl->assign( 'user_address', $uid.'@'.$_SERVER['SERVER_NAME'] );
$tmpl->assign( 'uid', $uid );
$tmpl->assign( 'storage_origin', $storage_origin );
$tmpl->assign( 'apps', $apps );
$tmpl->printPage();
