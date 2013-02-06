<?php
/**
 * Copyright (c) 2012 Michiel de Jong <michiel@unhosted.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

//params:
// origin      string
// launch_path string
// name        string
// scope       array( module => level )

function handle() {
  try {
    $params = json_decode(file_get_contents('php://input'), true);
  } catch(Exception $e) {
    OCP\JSON::error('post a JSON string please');
    return;
  }
  OCP\JSON::checkLoggedIn();
  OCP\JSON::checkAppEnabled('open_web_apps');
  OCP\JSON::callCheck();

  $manifestPath = 'apps/'.$params['name'].'/manifest.json';

  $uid = OCP\USER::getUser();
  $token = base64_encode(OC_Util::generate_random_bytes(40));
  try {
    $stmt = OCP\DB::prepare( 'INSERT INTO `*PREFIX*open_web_apps` (`uid_owner`, `manifest_path`, `access_token`) VALUES (?, ?, ?)' );
    $result = $stmt->execute(array($uid, $manifestPath, $token));
  } catch(Exception $e) {
    var_dump($e);
    OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
    OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' uid: '.$uid, OCP\Util::DEBUG);
    return false;
  }
  foreach($params['scope'] as $module => $level) {
    try {
      $stmt = OCP\DB::prepare( 'INSERT INTO `*PREFIX*remotestorage_access` (`access_token`, `module`, `level`) VALUES (?, ?, ?)' );
      $result = $stmt->execute(array($token, $module, $level));
    } catch(Exception $e) {
      var_dump($e);
      OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
      OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' uid: '.$uid, OCP\Util::DEBUG);
      return false;
    }
  }
  MyStorage.store($manifestPath, json_encode(array(
    'origin' => $params['origin'],
    'launch_path' => $params['launch_path'],
    'name' => $params['name']
  ), true));
  OCP\JSON::success(array('token'=>$token));
}
handle();
