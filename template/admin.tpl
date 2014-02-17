{combine_script id='ajaxq' load='header' require='jquery' path="$plugin_folder/template/js/ajaxq.js"}
{combine_script id='treat' load='header' require='jquery' path="$plugin_folder/template/js/admin.js"}
{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_script id='jquery.ui.progressbar' load='footer'}
{combine_css path="themes/default/js/plugins/jquery.jgrowl.css"}
{combine_css path="$plugin_folder/template/admin.css"}
{include file='include/colorbox.inc.tpl'}

{html_head}{literal}
<script type="text/javascript">
	pluginPath='{/literal}{$plugin_folder}{literal}';
</script>
{/literal}{/html_head}

{include file="../../../../$plugin_folder/template/popups.inc.tpl"}

<div class="titrePage">
  <h2>
    Dossier: <span id="fullDir"><a onclick='changeFolder("");'>{$conf.photos_local_folder}</a><span id="chemin"></span></span>
  </h2>
</div>

<fieldset id="progress" style="display:none">
    <legend>Ajout des photos au serveur</legend>

  <div id="status" class="start">
    Nombre de photos à envoyer:<span id="nbRestant"></span>/<span id="nbTotal"></span>
    <div id="progressbar"></div>
  </div>

 <div id="status" class="end" style="display:none"></div>

</fieldset>

<fieldset id="album">
	<legend>Configuration de l'album</legend>
	
  <p><input type="button" id="launch" value="Ajouter ce dossier au site"></p>
  
  <div>
	<span id="albumSelection"{if count($category_options) == 0} style="display:none"{/if}>
	  Choix de l'album: 
	  <select id="albumSelect" name="category">
		{html_options options=$category_options selected=$category_options_selected}
	  </select>
	  <br>... ou </span><a href="#" class="addAlbumOpen" title="Créer un nouvel album">créer un nouvel album</a>
  </div>
  
  <div id="level">
	Qui peut voir ces photos?
	  <select name="level" size="1">
		{html_options options=$level_options selected=$level_options_selected}
	  </select>
  </div>
  
</fieldset>

<fieldset id="origine" class="reference">
  <legend>
    Contenu du dossier
    <span id="loadingMissing" style="display:none">
      <img src="./themes/default/images/ajax-loader-small.gif"/>
    </span>
    <span class="missing"></span>
  </legend>
  
  <div id="thumb" style="display:none"><div id="thumbName"></div><img src="" alt="Preview"/></div>
  <iframe id="browser" src="{$plugin_folder}template/browse.php" ></iframe>
  <div id="waitBrowser" class="loadingBig"></div>

</fieldset>


