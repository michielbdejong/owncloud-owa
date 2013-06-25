var allowBtn = document.getElementById('allowBtn');
if(allowBtn) {
  allowBtn.onclick = function() {
    addApp(
      allowBtn.getAttribute('data-launch-url'),
      allowBtn.getAttribute('data-name'),
      allowBtn.getAttribute('data-scope')
    );
  };
}
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

  function addManifest() {
    var manifestUrl = document.getElementById('manifestUrl').value;
    ajax('addmanifest.php', {
      manifest_url_dirty: manifestUrl
    }, function() {
     window.location = '';
    });
  }

  function addApp(launchUrl, name, scope) {
    ajax('addapp.php', {
      launch_url: launchUrl,
      name: name,
      scope: scope
    }, function() {
     window.location = '';
    });
  }

  function removeApp(token) {
    ajax('removeapp.php', {
      id: id
    }, function() {
     window.location = '';
    });
  }

var addManifestBtn = document.getElementById('addManifestBtn');
if(addManifestBtn) {
  addManifestBtn.onclick = addManifest;
}
