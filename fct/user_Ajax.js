
$(document).ready(function() {
	$('.selectUser').click(function() {
		var selected = $(this).attr('id');
		var prenomUser = $(this).attr('nom');
		var ajaxStr  = 'action=select&id='+selected;
		$('#nomUserModif').html(prenomUser);
		$('#listingPage').animate( {bottom: "185px", opacity: ".5"}, transition );
		$('#modifieurPage').show(transition);
		AjaxJson (ajaxStr, "user_actions", displaySelUser);
	});
	
	// modification d'un user
	$('#modifieurPage').on('click', '.modif', function () {
		var idUser		= $('#modUserId').val() ;
		var curAuth		= $("#modUserCurAuth").val();
		var auth		= $("#modifieurPage input:radio[name='auth']:checked").val();
		var ldap		= $('#modUserLDAP').val();
		var email		= $('#modUserEmail').val() ;
		var passw		= $('#modUserPass').val() ;
		var prenom		= $('#modUserPrenom').val() ;
		var nom			= $('#modUserNom').val() ;
		var level		= $('#modUserLevel').val() ;
		var tekosAssoc	= $('#modUserTekos').val() ;
		
		if ( email == '' ) {
			alert("Vous devez saisir l'adresse email !");
			return;
		}
		var AjaxStr = 'action=modif&id='+idUser+'&auth='+auth+'&email='+email+'&prenom='+prenom+'&nom='+nom
						+'&level='+level+'&idTekos='+tekosAssoc ;
		switch(auth) {
			case 'DB':
				if (passw != '' || curAuth != auth) {
					if (passw.length < 4) {
						alert('Mot de passe trop court !');
						return;
					}
					AjaxStr += '&password='+encodeURIComponent(passw);
				}
				break;
			case 'LDAP':
				if ( ldap == '' ) {
					alert("Vous devez saisir le login LDAP !");
					return;
				}
				AjaxStr += '&ldap='+ldap ;
				if (idUser == $('#modifInfoUserActif').val() && ldap != $('#modUserActifCurLDAP').val()) {
					passw = $('#modUserLDAPPass').val();
					if(passw == '') {
						alert('Si vous changez votre compte LDAP, vous devez saisir son mot de passe !');
						return;
					}
					AjaxStr += '&password='+encodeURIComponent(passw);
				}
				break;
			default:
				alert("Vous devez saisir le type d'authentification !");
				return; 
		}
		
		AjaxFct(AjaxStr, 'user_actions', false, 'retourAjax', 'personnel_list_utilisateurs');
	});
	$('#modUserLDAP').on('blur', function() {
		var divLDAPPass = $('#modUserDivAuthLDAPPass');
		var curLDAP = $('#modUserActifCurLDAP').val();
		if($('#modUserId').val() == $('#modifInfoUserActif').val() && ($(this).val() != curLDAP || curLDAP == '')) {
			divLDAPPass.show();
		} else {
			divLDAPPass.hide();
		}
	});
	
	// Création d'un user
	$("#btncreateUser").click ( function () {
		var auth  = $("#createUser input:radio[name='auth']:checked").val();
		var login = $("#cLogin").val();
		var pass  = $("#cPass").val();
		var pren  = $("#cPren").val();
		var name  = $("#cName").val();
		var level = $("#cLevel").val();
		var tekos = $("#cTekosAssoc").val();
		var dataStr = "action=create&cAuth="+auth;
		switch(auth) {
			case 'DB':
				if ( login == '' || pass == '' || name == '' ) { alert("Vous devez remplir tous les champs marqués d'une étoile !"); return; }
				if ( pass.length <= 4  ) { alert("Mot de passe trop court ! Il doit être supérieur à 4 caractères."); return; }
				dataStr += "&cLogin="+login+"&cPass="+encodeURIComponent(pass)+"&cName="+name+"&cPren="+pren;
				break;
			case 'LDAP':
				if ( login == '' ) { alert("Vous devez remplir tous les champs marqués d'une étoile !"); return; }
				dataStr += "&cLogin="+login;
				break;
			default:
				alert("Vous devez saisir le type d'authentification !");
				return; 
		}
		dataStr += "&cLevel="+level+"&cTekos="+tekos ;
		AjaxFct(dataStr, 'user_actions', false, 'retourAjax', 'personnel_list_utilisateurs');
	});
	
	// Suppression d'un user
	$('.deleteUser').click(function () {
		var id = $(this).attr('id');
		var nom = $(this).attr('nom');
		var AjaxStr = 'action=delete&toDelete='+id;
		if (confirm('Supprimer l\'utilisateur '+nom+' ?'))
			AjaxFct(AjaxStr, 'user_actions', false, 'retourAjax', 'personnel_list_utilisateurs');
	});
	
	
////////////////////////////////////////////////////////////////////////////////
//////////////////////////// POUR LA PARTIE DEBUG //////////////////////////////			@OBSOLETE
////////////////////////////////////////////////////////////////////////////////
	
	
    // modif de champ (partie debug)
    $("#modifUser").on ('click' , 'button', function (e){
        var id     = $("#nameCombo").val();
		var data = 'action=modif&ID='+id+'&' ;
        $(".modifiable").each ( function (ind, obj) {
			var id  = $(obj).attr("name") ;
			var val =  $(obj).val() ;
			// si le mot de passe est vide, on passe //
			if ( id == 'password' && val == '' ) return 0 ;
			data += id + '=' + val + '&' ;
		});
		AjaxFct(data, 'user_actions');
    });
	
	// Ajout de champ  (partie debug)
	$('.addChampBTN').click ( function () {
		var id        = $("#nameCombo").val();
		var action    = "action=modif&ID="+id+"&";
		var champName = $('#newChampName').val();
		var champVal  = $('#newChampVal').val();
		if (champName == '') {$("#debugAjax").html("<div class='pad20'>Il manque le nom du champ !</div>");return;}
		if (champVal  == '') {$("#debugAjax").html("<div class='pad20'>Il manque la valeur du champ !</div>");return;}
		var dataStr	  = action + champName + '=' + champVal ;
        if (confirm('Certain d\'ajouter le champ ' + champName + ' à la BDD ?')) {
			AjaxFct(dataStr, 'user_actions');
		}
	});

	// effacer un user  (partie debug)
    $("#btnDelUser").click ( function () {
		var id     = $("#nameCombo").val();
		var email  = $("#nameCombo option[value='"+id+"']").text()
		if ( email == undefined || email.length == 0) return ; 
		var action  = "action=delete"; 
		var newdata = "&toDelete=" + id ;
		var dataStr	= action + newdata;
        if (confirm('Confirmation : supprimer ' + email + ' définitivement')) {
			AjaxFct(dataStr, 'user_actions');
		}
		
	});

	// charger un user (partie debug)
	$("#nameCombo").change ( function (e) {
		var id = $(this).val() ;
		var newdata = "action=load&ID=" + id ;
		var dataStr	= newdata;

		$.ajax({
			url: "./fct/user_actions.php",
			type: "POST",
			data: dataStr,
			success: function (retour) {
				$("#modifUser").html('<table>');
				rep = jQuery.parseJSON(retour);
				jQuery.each( rep, function (i, val) {
					$("#modifUser").append( "<tr><td>" + i + "</td><td style='padding-left:10px;'><input class='modifiable' name='" + i + "' type='text' value=\'"+val+"\' /></td></tr>");
				});

				$("#modifUser").append('<tr><td>Password</td><td style="padding-left:10px;"><input class="modifiable" name="password" value="" /></td></tr>');
				$("#modifUser").append('</table>');
				$("#modifUser").append('<button class="bouton petit" id="btncreateUser">Modifier</button>');
				
			}
		});
	});
	
	
});

function displaySelUser (data) {
	if(data.ldap_uid) {
		$("#modUserCurAuth").val('LDAP');
		$('#modUserAuthLDAP').prop('checked', true);
		$('#modUserDivAuthDB').hide();
		$('#modUserDivAuthLDAP').show();
		$('#modUserDivAuthLDAPPass').hide();
		$('#modUserPass').prop('title', '');
	} else {
		$("#modUserCurAuth").val('DB');
		$('#modUserAuthDB').prop('checked', true);
		$('#modUserDivAuthDB').show();
		$('#modUserDivAuthLDAP').hide();
		if(data.id == $('#modifInfoUserActif').val()) {
			$('#modUserDivAuthLDAPPass').show();
		}
		$('#modUserPass').prop('title', 'Laissez vide si pas de modif.');
	}
	
	// Masquage du choix du type d'authentification s'il n'y en a qu'un possible
	// (sauf si l'utilisateur sélectionné est configuré avec un autre type, pour lui permettre de changer)
	var authDiv = $('#modUserDivAuth');
	if($('#modifieurPage input:radio[name="auth"]:not(:disabled)').is(':not(:checked)')) {
		authDiv.show();
	} else {
		authDiv.hide();
	}
	
	$('#modUserId').val(data.id);
	$('#modUserEmail').val(data.email);
	$('#modUserLDAP').val(data.ldap_uid);
	$('#modUserLDAPPass').val('');
	$('#modUserPrenom').val(data.prenom);
	$('#modUserNom').val(data.nom);
	$('#modUserLevel').val(data.level);
	$('#modUserTekos').val(data.idTekos);
}