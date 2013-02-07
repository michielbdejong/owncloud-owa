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
// scope_map   array( module => level )

require('open_web_apps/lib/apps.php');

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

  if(MyApps::store($params['origin'], $params['launch_path'], $params['name'], '/favicon.ico', $params['scope_map'])) {
    OCP\JSON::success(array('token'=>$token));
  } else {
    OCP\JSON::failure(array());
  }
}
handle();
