{combine_script id='ajaxq' load='header' require='jquery' path="$plugin_folder/template/js/jquery.ajaxq.js"}

{html_head}{literal}<style type="text/css">
  #tableau {max-height: 480px; overflow-y:auto;}
  #progress {margin: 10px;}
  #launch {margin-left: 10px;}
</style>{/literal}{/html_head}

{footer_script}{literal}
$("input#launch").click(function() {
  $("input#launch").hide(); 
  $("tr.img").each(function (index) { // Boucle sur toutes les entrées de type 'img'
    $.ajaxq("fichiers",{
      url: 'ws.php?format=json',
      data: { method: 'pwg.images.addFromServer',
              image_path: "{/literal}{$dossier}{literal}"+$(this).children("td.entry").html(),
              category: {/literal}{$category_id}{literal},
              level: {/literal}{$level_id}{literal},
			  tags: "{/literal}{$systematic_tag}{literal}"
			},
	  beforeSend: jQuery.proxy(function() {
		$(this).children("td.status").html("en cours d'envoi");
	  },$(this)),
      datatype: 'json',
      success: jQuery.proxy(function(data) {
        status = jQuery.parseJSON(data).stat;
        if (status == "ok") // Si la requête n'a pas échoué
          $(this).children("td.status").html("photo ajoutée: <a href='" + jQuery.parseJSON(data).result.url + "' target='_blank'>Lien</a>");
        else {
          $(this).children("td.status").html("erreur: " + jQuery.parseJSON(data).message);
		  $("#fail").html(parseInt($("#fail").html())+1);
		}
		remaining = parseInt($("#nombre").html());
		if(remaining > 1)
			$("#nombre").html(remaining-1);
		else {
			$(".start").hide();
			$(".end").show();
		}
      },$(this))
    });
  });
});
{/literal}{/footer_script}

<div class="titrePage">
  <h2>Ajout des photos au serveur</h2>
</div>

<fieldset>
  <legend>Dossier à traiter: {$dossier}</legend>
  
  <div class="end" style="display:none">
    <a href="javascript:window.history.back()">Traiter un nouveau dossier</a>
  </div>
  <div id="progress" class="start">
    Nombre de photos à envoyer: <span id="nombre">{$content.nbImages}</span>/{$content.nbImages}<input type="button" id="launch" value="Lancer"/> 
  </div>
  <div id="progress" class="end" style="display:none">
    Images envoyées. <span id="fail">0</span> erreur(s) parmi les {$content.nbImages} photos. <a href="index.php?/category/{$category_id}" target="_blank">Afficher l'album</a>
  </div>
  <div id="tableau">  
    <table>
      <tr class="throw">
        <td>Fichier</td>
        <td>Statut</td>
      </tr>
      {foreach from=$content.entries key=ligne item=type name=entries}
        <tr valign="top" class="{if $smarty.foreach.entries.index is odd}row1{else}row2{/if} {$type}">
          <td class="entry">{$ligne}</td>
          <td class="status">{if $type != "img"}ne sera pas traité{else}en attente{/if}</td>
        </tr>
      {/foreach}
    </table>
  </div>
  
</fieldset>