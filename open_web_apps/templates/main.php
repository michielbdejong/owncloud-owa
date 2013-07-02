<style> .square { border-style: solid; border-width: 2px; float: left; width: 160px; height: 160px; display: block; overflow: hidden; text-align: center; border-radius: 5px } </style>
<div style="width:100%" id="icons">
<?php
  foreach($_['apps'] as $id => $obj) {
    if(isset($_['scope_diff_id']) && $_['scope_diff_id']==$id) {
      echo '<div class="square" style="border-style:dotted">'
        . '<img width="128px" height="128px" src="'.$obj['icon_url'].'">'
        . '<p>' . $obj['name'] . '</p></div>'
        . '<p>wants '.$_['scope_diff_add']['human']
        .'. <input type="submit" value="Allow" id="allowBtn"'
        .'  data-launch-url="'.$obj['launch_url'].'"'
        .'  data-name="'.$obj['name'].'"'
        .'  data-scope="'.$_['scope_diff_add']['normalized'].'"'
        .' /></p>';
    } else {
      echo '<div class="square">'
        . '<a href="' . $obj['launch_url']
        . '#remotestorage=' . urlencode($_['user_address'])
        . '&access_token=' . urlencode($obj['token'])
        . '&scope=' . urlencode($obj['scope'])
        . '">'
        . '<img width="128px" height="128px" src="' . $obj['icon_url']
        . '">'
        . '<p>' . $obj['name'] . '</p>'
        . '</a> </div>';
    }
  }
  if(isset($_['adding_id']) && $_['adding_id']) {
    echo '<div class="square" style="border-style:dotted">'
      . '<img width="128px" height="128px" src="">'
      . '<p>' . $_['adding_name'] . '</p></div>'
      . '<p>wants '.$_['adding_scope']['human']
      .'. <input type="submit" value="Install" id="allowBtn"'
        .'  data-launch-url="'.$_['adding_launch_url'].'"'
        .'  data-name="'.$_['adding_name'].'"'
        .'  data-scope="'.$_['adding_scope']['normalized'].'"'
      .' /></p>';
  }
?>
</div>
<div style=" clear: left ">
  Manifest: <input id="manifestUrl" value="https://music-michiel.5apps.com/michiel_music.webapp" style="width:20em" />
  <input type="submit" value="add manifest" id="addManifestBtn" />
</div>
<script src="/apps/open_web_apps/js/main.js"></script>
