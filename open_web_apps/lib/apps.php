<?php

class MyApps {
  public static function store($id, $launchPath, $name, $icon, $scopeMap) {
    $manifestPath = 'apps/'.$id.'/manifest.json';
    $uid = OCP\USER::getUser();
    $token = base64_encode(OC_Util::generate_random_bytes(40));
    try {
      $stmt = OCP\DB::prepare( 'INSERT INTO `*PREFIX*open_web_apps` (`uid_owner`, `app_id`, `access_token`) VALUES (?, ?, ?)' );
      $result = $stmt->execute(array($uid, $id, $token));
    } catch(Exception $e) {
      var_dump($e);
      OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' exception: '.$e->getMessage(), OCP\Util::ERROR);
      OCP\Util::writeLog('open_web_apps', __CLASS__.'::'.__METHOD__.' uid: '.$uid, OCP\Util::DEBUG);
      return false;
    }
    foreach($scopeMap as $module => $level) {
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
    MyStorage::store($uid, $manifestPath, 'application/json', json_encode(array(
      'launch_path' => $launchPath,
      'name' => $name,
      'icons' => array(
        '128' => $icon
      )
    ), true));
    return $token;
  }
}
