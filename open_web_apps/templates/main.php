<style> .square { border-style:solid; border-width:1px; float:left; width:160px; height:160px; display:block; overflow:hidden; text-align:center} </style>
<style id="editMode"> .remove_ { display: inline; } </style>
<div style="width:100%" id="icons">
<?php
  foreach($_['apps'] as $launchUrl => $obj) {
    echo '<div class="square">'
      . '<span class="remove_" onclick="remove(\'' . $obj['token'] . '\');">X</span>'
      . '<a href="' . $launchUrl
      . '#remotestorage=' . urlencode($_['user_address'])
      . '&access_token=' . urlencode($obj['token'])
      . '&scope=' . urlencode($obj['scope'])
      . '">'
      . '<img width="128px" height="128px" src="' . htmlentities(
         $obj['icon']
      ) . '">'//TODO: there is probably a better way to escape the icon URL?
      . '<p>' . htmlentities($obj['name']) . '</p>'//TODO: there is probably a better way to escape the name field?
      . '</a> </div>';
  }
  if($_['adding_app']) {
    echo '<div class="square" style="border-style:dotted">'
      . '<img width="128px" height="128px" src="">'
      . '<p>' . htmlentities($_['adding_name']) . '</p>'//TODO: there is probably a better way to escape the name field?
      . '<p>This app wants '.htmlentities($_['adding_scope']['human'])
      .'. <input type="submit" value="Install" onclick="addApp();" /></p></div>';
  }
?>
</div>
<input id="mainButton" type="submit" value="edit" onclick="changeMode()">
<div id="updateDiv">
  Manifest: <input id="appsource" value="http://music.michiel.5apps.com/michiel_music.webapp" style="width:20em" />
  <input type="submit" value="add manifest" onclick="addManifest();" />
</div>
<script>
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

  function remove(token) {
    ajax('removeapp.php', {
      token: token
    }, function() {
     window.location = '?';
    });
  }

  function addApp() {
    ajax('addapp.php', {
      url: $_['adding_url'],
      name: $_['adding_name'],
      scope: $_['adding_scope']['normalized']
    }, function() {
     window.location = '?';
    });
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
</script>
