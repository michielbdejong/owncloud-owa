<?php
/**
 * Copyright (c) 2012 Michiel de Jong <michiel@unhosted.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

//params:
// origin        string
// manifest_path string
// scope_map     array( module => level )

require('open_web_apps/lib/apps.php');
require('open_web_apps/lib/parser.php');

function fetchManifest($url) {
  $str = Util::getUrlContent($url);
  try {
    $obj = json_decode($str);
  } catch(e) {
    OCP\JSON::error('manifest should be a JSON string please');
  }
  return array(
    'name' => MyParser::cleanName($obj['name']),
    'icon' => MyParser::cleanUrlPath($obj['icons']['128']),
    'launch_path' => MyParser::cleanUrlPath($obj['launch_path'])
  );
}
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

  $urlObj = MyParser::parseUrl($params['manifest_url_dirty'])['origin'];
  $manifestClean = fetchManifest($urlObj['clean']);
  if($manifest) {
    if(MyApps::store($urlObj['origin'], $manifestClean['launch_path'], $manifestClean['name'], $manifestClean['icon'], $params['scope_map'])) {
      OCP\JSON::success(array('token'=> $token));
    } else {
      OCP\JSON::error('adding failed');
    }
  } else {
    OCP\JSON::error('fetching failed');
  }
}
handle();
