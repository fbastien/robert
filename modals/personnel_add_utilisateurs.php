<?php
if (session_id() == '') session_start();
require_once ('initInclude.php');
require_once ('common.inc.php');		// OBLIGATOIRE pour les sessions, à placer TOUJOURS EN HAUT du code !!
require_once ('checkConnect.php' );

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

		// Fonction de mise à jour des champs en fonction du type d'authentification
		$("#createUser input:radio[name='auth']").change(function(eventObject) {
			if($('#cAuthDB').is(':checked')) {
				$('#lLogin').html('Email');
				$('#lPass').html('*');
				$('#cPass').prop('disabled', false).val('').prop('title', '');
				$('#cPren').prop('disabled', false).val('').prop('title', '');
				$('#cName').prop('disabled', false).val('').prop('title', '');
			} else if($('#cAuthLDAP').is(':checked')) {
				$('#lLogin').html('Login');
				$('#lPass').html('');
				$('#cPass').prop('disabled', true).val('').prop('title', 'Ce champ sera rempli avec les données LDAP');
				$('#cPren').prop('disabled', true).val(' (automatique)').prop('title', 'Ce champ sera rempli avec les données LDAP');
				$('#cName').prop('disabled', true).val(' (automatique)').prop('title', 'Ce champ sera rempli avec les données LDAP');
			}
		});
	});
</script>

<div id="createUser" class="debugSection ui-widget-content ui-corner-all ajouteurPage">
	<div class="ui-widget-header ui-corner-all">Créer un utilisateur</div>
	<br />
	<div class="inline top" style="width: 500px;">
		<div class="ui-widget-header ui-corner-all">Authentification : <b class="red">*</b></div>
		<input type="radio" id="cAuthDB" name="auth" value="<?php echo AUTH_DB; ?>" /> <label for="cAuthDB">Par email et mot de passe</label>
		<input type="radio" id="cAuthLDAP" name="auth" value="<?php echo AUTH_LDAP; ?>" /> <label for="cAuthLDAP">Avec un compte LDAP</label>
	</div>
	<br />
	<br />
	<div class="inline top" style="width: 200px;">
		<div class="ui-widget-header ui-corner-all"><span id="lLogin">Email</span> : <b class="red">*</b></div>
		<input type="text" id="cLogin" size="20" />
		<br />
		<div class="ui-widget-header ui-corner-all">Mot de passe : <b class="red" id="lPass">*</b></div>
		<input type="password" id="cPass" size="20" />
	</div>
	<div class="inline top" style="width: 200px;">
		<div class="ui-widget-header ui-corner-all">Prénom :</div>
		<input type="text" id="cPren" size="20" />
		<br />
		<div class="ui-widget-header ui-corner-all">Nom :</div>
		<input type="text" id="cName" size="20" />
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