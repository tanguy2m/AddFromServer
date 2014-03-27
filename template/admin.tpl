{combine_script id='ajaxq' load='header' require='jquery' path="$plugin_folder/template/js/ajaxq.js"}
{combine_script id='treat' load='header' require='jquery' path="$plugin_folder/template/js/admin.js"}
{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_script id='jquery.ui.progressbar' load='footer'}
{combine_css path="themes/default/js/plugins/jquery.jgrowl.css"}
{combine_css path="$plugin_folder/template/admin.css"}
{include file='include/colorbox.inc.tpl'}

{* Category creation pop-up *}
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

<div class="titrePage">
  <h2>Ajouter des photos [Locales ex:NAS]</h2>
</div>

<fieldset> 
  <div id="browser"></div>
</fieldset>