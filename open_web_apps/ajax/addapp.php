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
require('open_web_apps/lib/parser.php');

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
  $urlObj = MyParser::parseUrl($params['launch_url']);
  $name = MyParser::cleanName($params['name']);
  $token = MyApps::store($urlObj['id'], $urlObj['path'], $name, '/favicon.ico', $params['scope_map']);
  if($token) {
    OCP\JSON::success(array());
  } else {
    OCP\JSON::failure(array());
  }
}
handle();
