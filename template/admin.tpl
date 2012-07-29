{combine_script id='ajaxq' load='header' require='jquery' path="$plugin_folder/template/js/jquery.ajaxq.js"}
{combine_script id='treat' load='header' require='jquery' path="$plugin_folder/template/js/admin.js"}
{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_css path="admin/themes/default/uploadify.jGrowl.css"}
{combine_css path="$plugin_folder/template/admin.css"}
{include file='include/colorbox.inc.tpl'}

{html_head}{literal}
<script type="text/javascript">
	pluginPath='{/literal}{$plugin_folder}{literal}';
	photosPath='{/literal}{$conf.photos_local_folder}{literal}';
	photosBinPath='{/literal}{$conf.photos_bin_folder}{literal}';
    systematic_tag='{/literal}{$conf.systematic_tag}{literal}';
</script>
{/literal}{/html_head}

<div class="titrePage">
  <h2></h2>
</div>

<fieldset id="origine" class="reference">
  <legend>Dossier: <span id="fullDir">{$conf.photos_local_folder}<span id="chemin"></span></span></legend>

  <iframe id="browser" src="{$plugin_folder}template/browse.php" ></iframe>
  <div id="waitBrowser" class="loadingBig"></div>

  <div class="reference">
    <div id="cheminFichier">Sélectionner une photo pour afficher un aperçu</div>
    <img id="suppr" src="{$ROOT_URL}{$themeconf.admin_icon_dir}/category_delete.png" title="Supprimer la photo" style="display:none">
  </div>
  <div id="miniature"></div> 

</fieldset>
  
<div style="display:none">
  <div id="addAlbumForm" style="text-align:left;padding:1em;">
    <form>
      Album parent<br>
      <select id ="category_parent" name="category_parent">
        <option value="0">------------</option>
        {html_options options=$category_options selected=$category_options_selected}
      </select>

      <br><br>Nom de l'album<br><input name="category_name" type="text"> <span id="categoryNameError"></span>
      <br><br><br><input type="submit" value="Créer"> <span id="albumCreationLoading" style="display:none"><img src="themes/default/images/ajax-loader-small.gif"></span>
    </form>
  </div>
</div>
  
<fieldset id="album">
    <legend>Paramètres Piwigo</legend>
    
  <div>
    <span id="albumSelection"{if count($category_options) == 0} style="display:none"{/if}>
      Choix de l'album: 
      <select id="albumSelect" name="category">
        {html_options options=$category_options selected=$category_options_selected}
      </select>   ... ou </span><a href="#" class="addAlbumOpen" title="Créer un nouvel album">créer un nouvel album</a>
  </div>
  
  <div id="level">
    Qui peut voir ces photos?
      <select name="level" size="1">
        {html_options options=$level_options selected=$level_options_selected}
      </select>
  </div>
</fieldset>

<form>
  <p id="submit"><input type="button" id="launch" value="Ajouter ce dossier à Piwigo"></p>
</form>

<fieldset id="progress" style="display:none">
    <legend>Ajout des photos au serveur</legend>

  <div id="status" class="start">
      Nombre de photos à envoyer: <span id="nbRestant"></span>/<span id="nbTotal"></span>
  </div>

 <div id="status" class="end" style="display:none"></div>

</fieldset>

