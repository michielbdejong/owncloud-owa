<style> .square { border-style:solid; border-width:1px; float:left; width:160px; height:160px; display:block; overflow:hidden; text-align:center} </style>
<style id="editMode"> .remove_ { display: inline; } </style>
<div style="width:100%" id="icons">
<?php
  for($i=0; $i<count($_['apps']);$i++) {
    if($_['apps'][$i]['manifest']) {
      $scopes = array();
      $scopeStrParts = array();
      foreach($_['apps'][$i]['scopes']['r'] as $module) {
        $scopes[$module] = 'r'; 
      }
      foreach($_['apps'][$i]['scopes']['w'] as $module) {
        $scopes[$module] = 'rw';
      }
      foreach($scopes as $module => $level) {
        $scopeStrParts[] = $module+':'+$level;
      }
      echo '<div class="square">'
        . '<span class="remove_" onclick="remove(\'' . $_['apps'][$i]['access_token'] . '\');">X</span>'
        . '<a href="' . $_['apps'][$i]['manifest']['origin'] . $_['apps'][$i]['manifest']['launch_path']
        . '#remotestorage=' . urlencode($_['user_address'])
        . '&access_token=' . urlencode($_['apps'][$i]['access_token'])
        . '&scope=' . urlencode($scopeStrParts.implode(' '))
        . '">'
        . '<img width="128px" height="128px" src="' . htmlentities(
           $_['apps'][$i]['manifest']['origin'] . $_['apps'][$i]['manifest']['icons']['128']
        ) . '">'//TODO: there is probably a better way to escape the icon URL?
        . '<p>' . htmlentities($_['apps'][$i]['manifest']['name']) . '</p>'//TODO: there is probably a better way to escape the name field?
        . '</a> </div>';
    }
  }
?>
</div>
<input id="mainButton" type="submit" value="edit" onclick="changeMode()">
<div id="updateDiv">
  <!--
    <input type="submit" value="install default apps" onclick="installDefaultApps();">
    Source: <input id="appsource" value="https://apps.unhosted.org/default.json" style="width:20em">
    <input type="submit" value="add launch URL" onclick="addLaunchURL();">
  -->
  Manifest: <input id="appsource" value="http://music.michiel.5apps.com/michiel_music.webapp" style="width:20em">
  
  <input type="submit" value="add manifest" onclick="addManifest();">
</div>
<div id="addDiv"></div>
<script>
  var parsedParams = { };
  function addApp() {
    console.log('installing:');
    console.log(parsedParams);
    installApp(parsedParams);
  }
      
  function checkForAdd() {
    var rawParams = location.search.substring(1).split('&');
    for(var i=0; i<rawParams.length; i++) {
      var parts = rawParams[i].split('=');
      if(parts[0]=='response_type' && parts[1]=='token') {
        parsedParams.addRequested = true;
      } else if(parts[0]=='redirect_uri') {
        var parser = document.createElement('a');
        parser.href = decodeURIComponent(parts[1]);
        parsedParams.name = parser.hostname;
        parsedParams.origin = parser.protocol + '//' + parser.host;
        parsedParams.launch_path = parser.pathname;
      } else if(parts[0]=='scope') {
        parsedParams.permissions = {};
        var scopeParts = decodeURIComponent(parts[1]).split(' ');
        for(var j=0; j<scopeParts.length; j++) {
          var scopePartParts = scopeParts[j].split(':');
          if(scopePartParts[0]=='') {
            scopePartParts[0]='root';
          }
          parsedParams.permissions[scopePartParts[0].replace(/[^a-z]/, '')] = {
            description: 'Requested by the app in the OAuth dialog',
            access: (scopePartParts[1]=='r'?'readonly':'readwrite')
          };
        }
      }
    }
    if(parsedParams.addRequested) {
      //if(haveAppAlready()) {
      //  window.location = makeLaunchURL(parsedParams, ...
      //} else { ... 
      var str = 'Give '+parsedParams.name+' access to '
      for(var i in parsedParams.permissions) {
        if(i=='root') {
          str += 'everything';
        } else if(i=='apps') {
          str += 'which apps you have installed';
        } else {
          str += i;
        }
        if(parsedParams.permissions[i]=='readonly') {
          str += ' (read only)';
        }
        str += ', ';
      }
      str = str.substring(0, str.length -2)+'.';
      document.getElementById('addDiv').innerHTML = str;
      mode='add';
      showMode();
    }
  }
  function changeMode() {
    if(mode=='main') {
      mode = 'edit';
    } else if(mode=='edit') {
      mode = 'main';
    } else {//mode=='add'
      addApp();
      mode = 'main';
    }
    showMode();
  }
  function showMode() {
    document.getElementById('mainButton').value = (mode=='edit'?'done':(mode=='add'?'add':'edit'));
    document.getElementById('addDiv').style.display = (mode=='add'?'block':'none');
    document.getElementById('updateDiv').style.display = (mode=='edit'?'block':'none');
    document.getElementById('editMode').innerHTML='.remove_ {display:'+(mode=='edit'?'inline':'none')+';}';
  }
  var mode = 'main';
  showMode();
  checkForAdd(); 
function ajax(endpoint, params, cb) {
  var xhr = new XMLHttpRequest();
  var path = '/?app=open_web_apps&getfile=ajax/'+endpoint;
  xhr.open('POST', path, true);
  xhr.onreadystatechange = function() {
    if(xhr.readyState == 4) {
      if(xhr.status==200) {
        result = {
          contentType: xhr.getResponseHeader('Content-Type'),
          content: xhr.responseText
        };
        cb(null, result);
      } else {
        console.log('ajax fail 3');
        cb(xhr.status);
      }
    }
  };
  xhr.setRequestHeader('requesttoken', oc_requesttoken);
  xhr.send(JSON.stringify(params));
}
function addLaunchURL() {
  installApp({
    "origin":document.getElementById('appsource').value,
    "launch_path":"/",
    "name":document.getElementById('appsource').value,
    "permissions":{}
  });
}
function addManifest() {
  var manifestURL = document.getElementById('appsource').value;
  retrieve(manifestURL, function(err1, data1) {
    var app;
    try {
      app = JSON.parse(data1.content);
    } catch(e) {
    }  
    if(app) {
      app.origin = manifestURL.split('/').slice(0, 3).join('/');
      installApp(app);
    }
  });
}
function installApp(manifestObj) {
    manifestObj.slug = manifestObj.name.toLowerCase().replace(/[^a-z0-9\ ]/, '').replace(' ', '-');
    manifestObj.manifest_path = 'apps/'+manifestObj.slug+'/manifest.json';
    ajax('storemanifest.php', manifestObj, function(err1, data1) {
      if(err1) {
        console.log(err1, data1);
      } else {
        var scopesObj = {r:[], w:[]};
        for(var i in manifestObj.permissions) {
          scopesObj.r.push(i);
          if(manifestObj.permissions[i]!='readonly') {
            scopesObj.w.push(i);
          }
        }
        ajax('addapp.php', {
          manifest_path: 'apps/'+manifestObj.slug+'/manifest.json',
          scopes: JSON.stringify(scopesObj)
        }, function(err2, data2) {
          if(err2) {
            console.log(err2, data2);
          } else {
            ajax('addapp.php', {
              manifest_path: 'appsapp',
              scopes: JSON.stringify({r:['apps'], w:['apps']})
            }, function(err3, data3) {
              if(err3) {
                console.log(err3, data3);
              } else {
                render();
              }
          });
        }
      });
    }
  });
}
function remove(token) {
  ajax('removeapp.php', {
    token: token
  }, function(err, data) {
    render();
  });
}

</script>
