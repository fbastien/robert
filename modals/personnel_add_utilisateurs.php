<?php
if (session_id() == '') session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

const STATE_LOGIN_LABEL = 0;
const STATE_FIELDS_ENABLED = 1;
const STATE_FIELDS_DEFAULT = 2;
const STATE_FIELDS_TITLE = 3;
//TODO PHP5.6+ const array
$STATE = array(
		AUTH_DB => array(
				STATE_LOGIN_LABEL => 'Email',
				STATE_FIELDS_ENABLED => true,
				STATE_FIELDS_DEFAULT => '',
				STATE_FIELDS_TITLE => ''),
		AUTH_LDAP => array(
				STATE_LOGIN_LABEL => 'Login',
				STATE_FIELDS_ENABLED => false,
				STATE_FIELDS_DEFAULT => ' (automatique)',
				STATE_FIELDS_TITLE => 'Ce champ sera rempli avec les données LDAP')
);

$hasAuthChoice = ($config[CONF_AUTH_DB] && $config[CONF_AUTH_LDAP]);
if ($config[CONF_AUTH_DB])
	$stateData = $STATE[AUTH_DB];
elseif ($config[CONF_AUTH_LDAP])
	$stateData = $STATE[AUTH_LDAP];
else
	die("Problème de configuration de l'authentification");

$l = new Liste();
$liste_tekos = $l->getListe(TABLE_TEKOS, '*', 'surnom', 'ASC', 'idUser', '<', 1);

?>
<script src="./fct/user_Ajax.js"></script>
<script>
	$(function() {
		$('.bouton').button();
		initToolTip('.tableListe', -120);
		
		// highlight des mini sous-menus
		$('.usersMiniSsMenu').addClass('ui-state-highlight');
		$('.miniSmenuBtn').removeClass('ui-state-highlight');
		$('#personnel_add_utilisateurs').addClass('ui-state-highlight');
		$('.usersMiniSsMenu').next().children().show(300);
		
		// on cache le bouton de recherche (pas besoin ici)
		$('#chercheDiv').hide(300);

<?php
if ($hasAuthChoice) {
	function echoJsAuthChange($state) {
		global $STATE;
		$stateData = $STATE[$state];
?>
				$('#lLogin').html('<?php echo $stateData[STATE_LOGIN_LABEL]; ?>');
				$('#lPass').html('<?php echo $stateData[STATE_FIELDS_ENABLED] ? '*' : ''; ?>');
				$('#cPass, #cPren, #cName').prop('disabled', <?php echo $stateData[STATE_FIELDS_ENABLED] ? 'false' : 'true'; ?>).prop('title', '<?php echo $stateData[STATE_FIELDS_TITLE]; ?>');
				$('#cPass').val('');
				$('#cPren, #cName').val('<?php echo $stateData[STATE_FIELDS_DEFAULT]; ?>');
<?php
	}
?>
		// Fonction de mise à jour des champs en fonction du type d'authentification
		$("#createUser input:radio[name='auth']").change(function(eventObject) {
			if($('#cAuthDB').is(':checked')) {
<?php echoJsAuthChange(AUTH_DB); ?>
			} else if($('#cAuthLDAP').is(':checked')) {
<?php echoJsAuthChange(AUTH_LDAP); ?>
			}
		});
<?php
}
?>
	});
</script>

<div id="createUser" class="debugSection ui-widget-content ui-corner-all ajouteurPage">
	<div class="ui-widget-header ui-corner-all">Créer un utilisateur</div>
	<div class="inline top" style="width: 500px; <?php echo $hasAuthChoice ? 'margin-bottom: 2ex;' : 'display: none;'; ?>">
		<br />
		<div class="ui-widget-header ui-corner-all">Authentification : <b class="red">*</b></div>
		<input type="radio" id="cAuthDB" name="auth" value="<?php echo AUTH_DB; ?>" <?php echo (! $hasAuthChoice && $config[CONF_AUTH_DB]) ? 'checked="checked"' : ''; ?> />
		<label for="cAuthDB">Par email et mot de passe</label>
		<input type="radio" id="cAuthLDAP" name="auth" value="<?php echo AUTH_LDAP; ?>" <?php echo (! $hasAuthChoice && $config[CONF_AUTH_LDAP]) ? 'checked="checked"' : ''; ?> />
		<label for="cAuthLDAP">Avec un compte LDAP</label>
	</div>
	<br />
	<div class="inline top" style="width: 200px;">
		<div class="ui-widget-header ui-corner-all"><span id="lLogin"><?php echo $stateData[STATE_LOGIN_LABEL]; ?></span> : <b class="red">*</b></div>
		<input type="text" id="cLogin" size="20" />
		<br />
		<div class="ui-widget-header ui-corner-all">Mot de passe : <b class="red" id="lPass"><?php echo $stateData[STATE_FIELDS_ENABLED] ? '*' : ''; ?></b></div>
		<input type="password" id="cPass" size="20" title="<?php echo $stateData[STATE_FIELDS_TITLE]; ?>" <?php echo $stateData[STATE_FIELDS_ENABLED] ? '' : 'disabled="disabled"'; ?> />
	</div>
	<div class="inline top" style="width: 200px;">
		<div class="ui-widget-header ui-corner-all">Prénom :</div>
		<input type="text" id="cPren" size="20" value="<?php echo $stateData[STATE_FIELDS_DEFAULT]; ?>"
				title="<?php echo $stateData[STATE_FIELDS_TITLE]; ?>" <?php echo $stateData[STATE_FIELDS_ENABLED] ? '' : 'disabled="disabled"'; ?> />
		<br />
		<div class="ui-widget-header ui-corner-all">Nom :</div>
		<input type="text" id="cName" size="20" value="<?php echo $stateData[STATE_FIELDS_DEFAULT]; ?>"
				title="<?php echo $stateData[STATE_FIELDS_TITLE]; ?>" <?php echo $stateData[STATE_FIELDS_ENABLED] ? '' : 'disabled="disabled"'; ?> />
	</div>
	<div class="inline top" style="width: 200px;">
		<div class="ui-widget-header ui-corner-all">Niveau d'habilitation :</div>
		<select id="cLevel" style="width: 150px;">
			<option value="1">Consultant</option>
			<option value="5">Utilisateur</option>
			<option value="7">Administrateur</option>
		</select>
		<br />
		<div class="ui-widget-header ui-corner-all">Technicien associé :</div>
		<select id="cTekosAssoc" style="width: 150px;">
			<option value="0"> ---- </option>
			<?php 
			foreach ($liste_tekos as $tekos)
				echo '<option value="'.$tekos['id'].'">'.$tekos['surnom'].'</option>'
			?>
		</select>
	</div>
	<div class="inline bot" style="width: 200px;">
		<button class="bouton petit" id="btncreateUser">Créer l'utilisateur</button>
	</div>
	<br />
</div>