<?php
if (session_id() == '') session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

$l = new Liste();
if ( isset($_POST['searchingfor']) ) {
	// Pour les codes-barres, requête imbriquée pour chercher aussi dans la table du matériel unitaire
	if($_POST['searchingwhat'] === 'codeBarres') {
		$l->setFiltreSQL('`codeBarres` LIKE \'%'.addSlashes($_POST['searchingfor']).'%\' OR EXISTS( SELECT * FROM `'.TABLE_MATOS_UNIT.'` WHERE `'.TABLE_MATOS_UNIT.'`.`id_matosdetail` = `'.TABLE_MATOS.'`.`id` AND `'.TABLE_MATOS_UNIT.'`.`ref` LIKE \'%'.addSlashes($_POST['searchingfor']).'%\' )');
		$liste_matos = $l->getListe(TABLE_MATOS, '*', 'ref');
	}
	else {
		$liste_matos = $l->getListe(TABLE_MATOS, '*', 'ref', 'ASC', $_POST['searchingwhat'], 'LIKE', '%'.$_POST['searchingfor'].'%');
	}
	$modeRecherche = true;
}
else {
	$liste_matos = $l->getListe(TABLE_MATOS, '*', 'ref');
}
unset($l);

$lm = new Liste();
$liste_ssCat = $lm->getListe(TABLE_MATOS_CATEG, '*', 'ordre', 'ASC');
$listeMatosUnit = $lm->getListe(TABLE_MATOS_UNIT, '*', 'ref');

?>
<script src="./fct/matos_Ajax.js"></script>
<script>
	$(function() {
		$('.bouton').button();
		initToolTip('.tableListe', -120);
		
		// highlight des mini sous-menus
		$('.detailMiniSsMenu').addClass('ui-state-highlight');
		$('.miniSmenuBtn').removeClass('ui-state-highlight');
		$('#matos_list_detail').addClass('ui-state-highlight');
		$('.detailMiniSsMenu').next().children().show(300);
		
		// init du system de recherche
		$('.chercheBtn').attr('id', 'matos_list_detail');	// ajoute le nom du fichier actuel (en id du bouton) pour la recherche
		$('#filtreCherche').html(							// Ajout des options de filtrage pour la recherche
			'<option value="label">Désignation</option>' +
			'<option value="ref">Référence</option>' +
			'<option value="codeBarres">Code-barres</option>' +
			'<option value="dateAchat">Année d\'achat</option>'
		);
		$('#chercheInput').val('');							// vide l'input de recherche
		$('#chercheDiv').show(300);							// affiche le module de recherche
		$('#filtresDiv').show(300);							// affiche le module des filtres
		$('#affichageDiv').show(300);						// affiche les boutons d'affichage
		$('#polyvalent').hide();							// sauf le 'polyvalent' (existe que pour les packs)
		$('.filtre').removeClass('ui-state-error');
		
		$(".inputCal2").datepicker({dateFormat: 'yy-mm-dd', firstDay: 1, changeMonth: true, changeYear: true});
	});
</script>


<div class="ui-widget-content ui-corner-all" id="listingPage">
	<div class="ui-widget-header ui-corner-all gros center pad3">Liste du matériel au détail</div>
	<br />
	<table class="tableListe">
		<tr class="titresListe">
			<th class="ui-state-disabled">Référence</th>
			<th class="ui-state-disabled">Code-barres</th>
			<th class="ui-state-disabled">Désignation complète</th>
			<th class="ui-state-disabled">Catégorie</th>
			<th class="ui-state-disabled">Tarif loc.</th>
			<th class="ui-state-disabled">Val. Remp.</th>
			<th class="ui-state-disabled">Qté Parc</th>
			<th class="ui-state-disabled">En panne</th>
			<th class="ui-state-disabled">Actions</th>
		</tr>
<?php
include('matos_tri_sousCat.php');

$matos_by_categ = creerSousCatArray($liste_matos);
$categById		= simplifySousCatArray($liste_ssCat);
$unitsByMatos = groupUnitsByMatos($listeMatosUnit);

if (is_array($matos_by_categ)) {
	foreach ($categById as $catInfo) {
		$index = $catInfo['id'];
		if (!is_array(@$matos_by_categ[$index]))
			continue; // n'affiche rien si la sous catégorie est vide !
?>
		<tr class="ui-state-hover sousCategLine">
			<td colspan="9" class="leftText gros gras" style="padding-left:20px;"><?= $catInfo['label'] ?></td>
		</tr>
<?php
		foreach ($matos_by_categ[$index] as $info) {
			$isExterne = ($info['ownerExt'] !== null);
?>
		<tr class="ui-state-default matosLine <?= $isExterne ? 'matosExterne '.(@$modeRecherche != true ? 'hide' : 'ui-state-active') : 'matosInterne' ?> cat-<?= $info['categorie'] ?>">
			<td><?= $info['ref'] ?></td>
			<td><?= $info['codeBarres'] ?></td>
			<td popup="<?= preg_replace('/\\n/', '<br />', addslashes($info['remarque'])) ?>">
				<?= $info['label'] ?></td>
			<td><img src="./gfx/icones/categ-<?= $info['categorie'] ?>.png" alt="<?= $info['categorie'] ?>" /></td>
			<td><?= $info['tarifLoc'] ?> &euro;</td>
			<td><?= $info['valRemp'] ?> &euro;</td>
			<td <?php if($isExterne) { ?>class="ui-state-error" popup="EXTERNE AU PARC !&lt;br /&gt;&lt;br /&gt;A louer chez : &lt;b&gt;<?= $info['ownerExt'] ?>&lt;/b&gt;"<?php } ?>>
				<?= $info['Qtotale'] ?></td>
			<td <?php if($info['panne'] >= 1) { ?>class="ui-state-error"<?php } ?>>
				<?= $info['panne'] ?></td>
			<td class="rightText printHide">
<?php if (isset($unitsByMatos[$info['id']])) { ?>
				<button class="bouton showMDtr" id="<?= $info['id'] ?>" title="Afficher le matériel identifié"><span class="ui-icon ui-icon-tag"></span></button>
<?php } ?>
<?php if ( $_SESSION['user']->isLevelMod() ) { ?>
				<button class="bouton selectMatos" id="<?= $info['id'] ?>" nom="<?= $info['ref'] ?>" title="modifier"><span class="ui-icon ui-icon-pencil"></span></button>
				<button class="bouton deleteMatos" id="<?= $info['id'] ?>" nom="<?= $info['ref'] ?>" title="supprimer"><span class="ui-icon ui-icon-trash"></span></button>
<?php } ?>
			</td>
		</tr>
<?php
			if(isset($unitsByMatos[$info['id']])) {
?>
		<tr class="shadowIn center mDetail hide" id="matosDetailTR-<?= $info['id'] ?>">
			<td colspan="2" valign="top" class="pad20"><br />Matériel <b>"<?= $info['ref'] ?>"</b> identifié :</td>
			<td colspan="7" class="leftText pad20"><br />
<?php
				foreach ($unitsByMatos[$info['id']] as $infoUnit) {
?>
				<div class="inline padV10 top <?= $infoUnit['panne'] ? 'ui-state-error' : '' ?>" style="width: 20%;">
					<?= $infoUnit['ref'] ?>
					<?php if($infoUnit['panne']) { ?><i class="mini">(en panne)</i><?php } ?>
				</div>
				<div class="inline padV10 top">
					<?= preg_replace('/\\n/', '<br />', addslashes($infoUnit['remarque'])) ?>
					<?= ($infoUnit['dateAchat'] && $infoUnit['dateAchat'] > 0) ? ' (acheté le '.$infoUnit['dateAchat'].')' : '' ?>
					<?= $infoUnit['ownerExt'] ? ' (chez '.$infoUnit['ownerExt'].')' : '' ?>
				</div><br />
<?php
				}
?>
				<br /></td>
		</tr>
<?php
			}
		}
	}
}
?>
	</table>
	<br />
</div>

<div class="ui-widget-content ui-corner-all center gros hide" id="modifieurPage" style="height: auto; max-height: 75%; min-height: 250px; overflow-y: auto;">
	<div class="closeModifieur ui-state-active ui-corner-all" id="btnClose"><span class="ui-icon ui-icon-circle-close"></span></div>
	<div class="ui-widget-header ui-corner-all pad3">Modifier le matériel "<span id="nomMatosModif"></span>"</div>
	<input type="hidden" id="modMatosId" />
	<div class="inline leftText marge30l margeTop5">
		<div class="ui-widget-header ui-corner-all center">Informations générales</div>
		<div class="ui-widget-content ui-corner-all" style="padding: 1ex;">
			<div class="inline top center pad3" style="width: 140px;">
				<div class="ui-widget-header ui-corner-all">Référence : <b class="red">*</b></div>
				<input type="text" id="modMatosRef" style="width: 100%;" />
			</div>
			<div class="inline top center pad3" style="width: 450px;">
				<div class="ui-widget-header ui-corner-all">Désignation complète : <b class="red">*</b></div>
				<input type="text" id="modMatosLabel" style="width: 100%;" />
			</div>
			<div class="inline top center pad3" style="width: 140px;">
				<div class="ui-widget-header ui-corner-all">Code-barres :</div>
				<input type="text" id="modMatosCode" style="width: 100%;" />
			</div>
			<br />
			<div class="inline top center pad3" style="width: 120px;">
				<div class="ui-widget-header ui-corner-all">Catégorie : <b class="red">*</b></div>
				<select id="modMatosCateg">
					<option value="son">SON</option>
					<option value="lumiere">LUMIÈRE</option>
					<option value="structure">STRUCTURE</option>
					<option value="transport">TRANSPORT</option>
				</select>
			</div>
			<div class="inline top center pad3" style="width: 190px;">
				<div class="ui-widget-header ui-corner-all">Sous Categ :</div>
				<select id="modMatosSousCateg">
					<option value="0">---</option>
					<?php
					foreach ($liste_ssCat as $ssCat) {
						echo '<option value="'.$ssCat['id'].'">'.$ssCat['label'].'</option>';
					}
					?>
				</select>
			</div>
			<div class="inline top center pad3" style="width: 105px;">
				<div class="ui-widget-header ui-corner-all">Tarif loc. <b class="red">*</b></div>
				<input class="NumericInput" type="text" id="modMatosTarif" size="5" /> €
			</div>
			<div class="inline top center pad3" style="width: 105px;">
				<div class="ui-widget-header ui-corner-all">Val. Remp. <b class="red">*</b></div>
				<input class="NumericInput" type="text" id="modMatosValRemp" size="6" /> €
			</div>
			<div class="inline top center pad3" style="width: 90px;">
				<div class="ui-widget-header ui-corner-all">Qté Parc <b class="red">*</b></div>
				<input class="NumericInput" type="text" id="modMatosQteTot" size="6" />
			</div>
			<div class="inline top center pad3" style="width: 90px;">
				<div class="ui-widget-header ui-corner-all">En panne</div>
				<input class="NumericInput" type="text" id="modMatosPanne" size="6" />
			</div>
			<br />
			<div class="inline top center pad3" style="width: 480px;">
				<div class="ui-widget-header ui-corner-all">Remarque :</div>
				<textarea id="modMatosRem" rows="5" style="width: 100%;"></textarea>
			</div>
			<div class="inline top center pad3" style="width: 130px;">
				<div class="ui-widget-header ui-corner-all">Externe ?</div>
				<input type="checkbox" id="modMatosExterne" class="externeBox" />
			</div>
			<div class="inline top center pad3" style="width: 120px;">
				<div id="dateAchatDiv">
					<div class="ui-widget-header ui-corner-all">Acheté le :</div>
					<input type="text" id="modMatosDateAchat" class="inputCal2" size="9" />
				</div>
				<div id="chezQuiDiv" class="hide">
					<div class="ui-widget-header ui-corner-all">À louer chez :</div>
					<input type="text" id="modMatosExtOwner" size="9" />
				</div>
			</div>
		</div>
		<br />
		<div class="ui-widget-header ui-corner-all center">Matériel identifié unitairement</div>
		<div class="ui-widget-content ui-corner-all" style="padding: 1ex;">
			<table id="modMatosListeUnits" class="tableListe">
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
			<br />
			<div class="big center" title="Ajouter un matériel identifié">
				<button class="bouton" id="addMatosUnit" onclick="addMatosUnitRow(false);"><span class="ui-icon ui-icon-plusthick"></span></button>
			</div>
		</div>
	</div>
	<div class="inline bot leftText pad10">
		<button class="bouton closeModifieur">ANNULER</button>
		<br /><br />
		<button class="bouton modif" id="matos">SAUVEGARDER</button>
	</div>
</div>

<div id="toolTipPopup" class="ui-widget ui-state-highlight ui-corner-all pad20"></div>
