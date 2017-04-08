<?php
if (session_id() == '') session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

$lm = new Liste();
$liste_ssCat = $lm->getListe(TABLE_MATOS_CATEG, '*', 'ordre', 'ASC');

?>

<script src="./fct/matos_Ajax.js"></script>
<script>
	$(function() {
		$('.bouton').button();

		// highlight des mini sous-menus
		$('.matosMiniSsMenu').addClass('ui-state-highlight');
		$('.miniSmenuBtn').removeClass('ui-state-highlight');
		$('#matos_add_detail').addClass('ui-state-highlight');
		$('.matosMiniSsMenu').next().children().show(300);

		// on cache le bouton de recherche et les filtres (pas besoin ici)
		$('#chercheDiv').hide(300);
		$('#filtresDiv').hide(300);

		// Calendrier sur focus d'input
		$(".inputCal2").datepicker({dateFormat: 'yy-mm-dd', firstDay: 1, changeMonth: true, changeYear: true});
	});
</script>


<div class="ui-widget-content ui-corner-all leftText ajouteurPage">
	<div class="ui-widget-header ui-corner-all center">Ajout de matériel</div>
	<br />
	<div class="ui-widget-header ui-corner-all center">Informations générales</div>
	<div class="ui-widget-content ui-corner-all" style="padding: 1ex;">
		<div class="inline top center pad3" style="width: 140px;">
			<div class="ui-widget-header ui-corner-all">Référence : <b class="red">*</b></div>
			<input type="text" id="newMatosRef" style="width: 100%;" />
		</div>
		<div class="inline top center pad3" style="width: 450px;">
			<div class="ui-widget-header ui-corner-all">Désignation complète : <b class="red">*</b></div>
			<input type="text" id="newMatosLabel" style="width: 100%;" />
		</div>
		<div class="inline top center pad3" style="width: 140px;">
			<div class="ui-widget-header ui-corner-all">Code-barres :</div>
			<input type="text" id="newMatosCode" style="width: 100%;" />
		</div>
		<br />
		<div class="inline top center pad3" style="width: 140px;">
			<div class="ui-widget-header ui-corner-all">Catégorie : <b class="red">*</b></div>
			<select id="newMatosCateg">
				<option value="son">SON</option>
				<option value="lumiere">LUMIÈRE</option>
				<option value="structure">STRUCTURE</option>
				<option value="transport">TRANSPORT</option>
			</select>
		</div>
		<div class="inline top center pad3" style="width: 200px;">
			<div class="ui-widget-header ui-corner-all">Sous Categ :</div>
			<select id="newMatosSousCateg">
				<option value="0">---</option>
				<?php
				foreach ($liste_ssCat as $ssCat) {
					echo '<option value="'.$ssCat['id'].'">'.$ssCat['label'].'</option>';
				}
				?>
			</select>
		</div>
		<div class="inline top center pad3" style="width: 120px;">
			<div class="ui-widget-header ui-corner-all">Tarif loc. : <b class="red">*</b></div>
			<input class="NumericInput" type="text" id="newMatosTarifLoc" size="6" /> €
		</div>
		<div class="inline top center pad3" style="width: 130px;">
			<div class="ui-widget-header ui-corner-all">Val. Remp. : <b class="red">*</b></div>
			<input class="NumericInput" type="text" id="newMatosValRemp" size="8" /> €
		</div>
		<div class="inline top center pad3" style="width: 120px;">
			<div class="ui-widget-header ui-corner-all">Qté Parc : <b class="red">*</b></div>
			<input class="NumericInput" type="text" id="newMatosQtotale" size="7" />
		</div>
		<br />
		<div class="inline top center pad3" style="width: 480px;">
			<div class="ui-widget-header ui-corner-all">Remarque :</div>
			<textarea id="newMatosRemark" rows="5" style="width: 100%;"></textarea>
		</div>
		<div class="inline top center pad3" style="width: 130px;">
			<div class="ui-widget-header ui-corner-all">Externe ?</div>
			<input type="checkbox" id="newMatosExterne" class="externeBox" />
		</div>
		<div class="inline top center pad3" style="width: 120px;">
			<div id="dateAchatDiv">
				<div class="ui-widget-header ui-corner-all">Acheté le :</div>
				<input type="text" id="newMatosDateAchat" class="inputCal2" size="9" />
			</div>
			<div id="chezQuiDiv" class="hide">
				<div class="ui-widget-header ui-corner-all">À louer chez :</div>
				<input type="text" id="newMatosExtOwner" size="9" />
			</div>
		</div>
	</div>
	<br />
	<div class="ui-widget-header ui-corner-all center">Matériel identifié unitairement</div>
	<div class="ui-widget-content ui-corner-all" style="padding: 1ex;">
		<div class="big center" title="Ajouter un matériel identifié">
			<button class="bouton" id="addMatosUnit" onclick="addMatosUnitRow(true);"><span class="ui-icon ui-icon-plusthick"></span></button>
		</div>
		<br />
		<table id="newMatosListeUnits" class="tableListe">
			<thead>
				<tr class="titresListe">
					<th>Code-barres <b class="red">*</b></th>
					<th colspan="2" style="text-align: left;">Externe ?</th>
					<th>Remarque</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	<br />
	<div class="center bot">
		<button class="bouton" id="addMatos">AJOUTER</button>
	</div>
</div>
