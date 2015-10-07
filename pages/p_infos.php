<?php
	if ( !isset($_SESSION["user"])) { header('Location: index.php'); }
	if ( $_SESSION["user"]->isAdmin() !== true ) { header('Location: index.php'); }
	require_once('common.inc.php');
?>

<script src="./fct/infos_Ajax.js"></script>

<div class="ui-state-error ui-corner-all center top gros" id="retourAjax"></div>


<div class="big">
	<div class="ui-widget-header ui-corner-all center">MODIFICATION DES INFORMATIONS</div>
</div>

<br /><br /><br />

<div class="marge30l gros" id="infosDiv">
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Raison Sociale</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.nom" value="<?php echo $config['boite.nom'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Status</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.type" value="<?php echo $config['boite.type'] ?>" size="20" /></div>
	</div>
	<br /><br />
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Adresse Postale</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.adresse.rue" value="<?php echo $config['boite.adresse.rue'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Code Postal</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.adresse.CP" value="<?php echo $config['boite.adresse.CP'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Ville</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.adresse.ville" value="<?php echo $config['boite.adresse.ville'] ?>" size="20" /></div>
	</div>
	<br /><br />
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">No de Téléphone</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.tel" value="<?php echo $config['boite.tel'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Adresse Email</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.email" value="<?php echo $config['boite.email'] ?>" size="20" /></div>
	</div>
	<br /><br />
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">No de SIRET</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.SIRET" value="<?php echo $config['boite.SIRET'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Code APE</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.APE" value="<?php echo $config['boite.APE'] ?>" size="20" /></div>
	</div>
	<br /><br />
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">No de TVA</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.TVA.num" value="<?php echo $config['boite.TVA.num'] ?>" size="20" /></div>
	</div>
	<div class="inline ui-widget-content ui-corner-all pad10">
		<div class="ui-widget-header ui-corner-all center">Valeur de TVA (%)</div>
		<div class="ui-state-default ui-corner-all"><input type="text" name="boite.TVA.val" value="<?php echo $config['boite.TVA.val'] * 100 ?>" size="20" /></div>
	</div>
</div>

<br /><br /><br />

<div class="marge30l big">
	<button class="bouton" id="saveInfos">ENREGISTRER les modifs</button>
</div>