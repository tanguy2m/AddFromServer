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

{* Categorie creation pop-up *}
<div style="display:none">
  <div id="addAlbumForm" style="text-align:left;padding:1em;">
    <form>
      Album parent<br>
      <select class="albumSelect" name="category_parent">
        <option value="0">------------</option>
        {html_options options=$category_options selected=$category_options_selected}
      </select>

      <br><br>Nom de l'album<br><input name="category_name" type="text"> <span id="categoryNameError"></span>
      <br><br><br><input type="submit" value="Créer"> <span id="albumCreationLoading" style="display:none"><img src="themes/default/images/ajax-loader-small.gif"></span>
    </form>
  </div>
</div>

{* Add to album form *}
<div class="addToAlbum" style="display:none">
	<p class="before"><input type="button" class="launch" value="Ajouter ce dossier au site"></p>

	<div class="before">
		<span>Choix de l'album:<select class="albumSelect" name="category"></select>
		<br>... ou </span><a href="#" class="addAlbumOpen" title="Créer un nouvel album">créer un nouvel album</a>
	</div>

	<div class="before">
		Qui peut voir ces photos?
		<select name="level" size="1">
			{html_options options=$level_options selected=$level_options_selected}
		</select>
	</div>
	
	<div class="during" style="display:none">
		Nombre de photos à envoyer:<span class="nbRestant"></span>/<span class="nbTotal"></span>
		<div class="progressbar"></div>
	</div>

	<div class="after" style="display:none">
		Images envoyées: <span class="nbErrors"></span> erreur(s) parmi les <span class="nbTotal"></span> photos.
		<a href="" target="_blank">Afficher l'album</a>
	</div>	
</div>

{* OBSO *}
<div class="titrePage">
  <h2>
    Dossier: <span id="fullDir"><a onclick='changeFolder("");'>{$conf.photos_local_folder}</a><span id="chemin"></span></span>
  </h2>
</div>

{* OBSO *}
<fieldset id="progress" style="display:none">
    <legend>Ajout des photos au serveur</legend>

  <div id="status" class="start">
    Nombre de photos à envoyer:<span id="nbRestant"></span>/<span id="nbTotal"></span>
    <div id="progressbar"></div>
  </div>

 <div id="status" class="end" style="display:none"></div>

</fieldset>

{* OBSO *}
<fieldset id="album">
	<legend>Configuration de l'album</legend>
	<div class="addToAlbum">
		<p><input type="button" id="launch" value="Ajouter ce dossier au site"></p>

		<div>
			<span>Choix de l'album:<select class="albumSelect" name="category"></select>
			<br>... ou </span><a href="#" class="addAlbumOpen" title="Créer un nouvel album">créer un nouvel album</a>
		</div>

		<div>
			Qui peut voir ces photos?
			<select name="level" size="1">
				{html_options options=$level_options selected=$level_options_selected}
			</select>
		</div>
	</div>
</fieldset>

<fieldset id="origine" class="reference">
  <legend>
    Contenu du dossier
    {* OBSO *}<span id="loadingMissing" style="display:none">
    {* OBSO *}  <img src="./themes/default/images/ajax-loader-small.gif"/>
    {* OBSO *}</span>
    {* OBSO *}<span class="missing"></span>
  </legend>
  
  <div id="thumb" style="display:none"><div id="thumbName"></div><img src="" alt="Preview"/></div>
  {* OBSO *}<iframe id="browser" src="{$plugin_folder}template/browse.php" ></iframe>
  {* OBSO *}<div id="waitBrowser" class="loadingBig"></div>
  <div id="navigateur"></div>

</fieldset>


