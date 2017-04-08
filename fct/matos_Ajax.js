
$(function() {

	///// Filtrage du matos et des packs par catégorie VERSION 2 (additif)
	$('#filtresDiv').off('click', '.filtre');
	$('#filtresDiv').on('click', '.filtre', function() {
		$('.matosLine').hide();
		$('.mDetail').hide();

		if ( $(this).hasClass('ui-state-error') )
			 $(this).removeClass('ui-state-error');
		else $(this).addClass('ui-state-error');

		var stillFiltred = false;
		var filtredExt	 = false;
		$('.filtre').each(function(i, obj){
			$('.sousCategLine').show();
			var categ = $(obj).attr('id');
			if ($(obj).hasClass('ui-state-error')) {
				if (categ == 'int-ext') {
					filtredExt = true;
				}
				else {
					$('.cat-'+categ).show(10, function(){refreshSousCatLine();});
					stillFiltred = true;
				}
			}
			else $('.cat-'+categ).hide(10, function(){refreshSousCatLine();});
		});

		if (stillFiltred == false) {
			$('.sousCategLine').show();
			$('.matosPik').show();
			$('.packPik').show();
			$('.matosLine').show();
			$('.matosExterne').hide();
		}

		if (filtredExt == true) {
			$('.matosInterne').hide();
			if (stillFiltred == false) {
				$('.matosExterne').show(10, function(){refreshSousCatLine();});
			}
		}
		else {
			$('.matosExterne').hide();
		}
	});


	// Affichage du détail d'un matos
	$('.matosLine').has('.showMDtr').click(function(event) {
		if($(event.target).is('button:not(.showMDtr'))
			return;
		$('#matosDetailTR-'+$(this).find('.showMDtr').attr('id')).toggle();
	});


	// sélection d'un matos
	$('.selectMatos').click(function() {
		var idSel  = $(this).attr('id');
		var nomSel = $(this).attr('nom');
		var ajaxStr  = 'action=select&id='+idSel;
		$('#nomMatosModif').html(nomSel);
		$('#listingPage').animate( {bottom: "255px", opacity: ".5"}, transition );
		$('#modifieurPage').show(transition);
		AjaxJson(ajaxStr, 'matos_actions', displaySelMatos);
	});


	// modification d'un matos
	$('#modifieurPage').on('click', '.modif', function () {
		var idMatos		= $('#modMatosId').val();
		var label		= encodeURIComponent($('#modMatosLabel').val());
		var ref			= $('#modMatosRef').val();
		var code		= $('#modMatosCode').val();
		var categ		= $('#modMatosCateg').val();
		var sscateg		= $('#modMatosSousCateg').val();
		var Qtotale		= $('#modMatosQteTot').val();
		var dateAchat	= $('#modMatosDateAchat').val();
		var ownerExt	= $('#modMatosExtOwner').val() ;
		var tarifLoc	= $('#modMatosTarif').val();
		var valRemp		= $('#modMatosValRemp').val();
		var panne		= $('#modMatosPanne').val();
		var remarque	= encodeURIComponent($('#modMatosRem').val());
		var externe		= ($('#modMatosExterne').attr('checked') ? 1 : 0);
		
		if (label.length == 0 || ref.length == 0 || categ.length == 0 || Qtotale.length == 0 || tarifLoc.length == 0 || valRemp.length == 0) {
			alert('Vous devez remplir tous les champs marqués d\'une étoile !');
			return;
		}
		if (! $.isNumeric(Qtotale) || Qtotale % 1 !== 0 || Qtotale <= 0) {
			alert('Quantité totale invalide !');
			return;
		}
		
		var ajaxStr = 'action=modif&id='+idMatos+'&label='+label+'&ref='+ref+'&codeBarres='+code
				+'&categorie='+categ+'&sousCateg='+sscateg
				+'&Qtotale='+Qtotale+'&dateAchat='+dateAchat
				+'&tarifLoc='+tarifLoc+'&valRemp='+valRemp+'&panne='+panne
				+'&externe='+externe+'&ownerExt='+ownerExt
				+'&remarque='+remarque ;
		
		var matosUnitsList = $('table#modMatosListeUnits > tbody > tr');
		if (Qtotale < matosUnitsList.length) {
			alert('Quantité totale inférieure au nombre de matériel identifié unitairement !');
			return;
		}
		
		var matosUnitRefs = [];
		var unitIndex = 0;
		for(var i = 0; i < matosUnitsList.length; i++) {
			var row = matosUnitsList.eq(i);
			var unitRef = row.find('.matosUnitRef').val();
			var unitExterne = (row.find('.matosUnitExterne').is(':checked') ? 1 : 0);
			var unitDateAchat = row.find('.matosUnitDateAchat').val();
			var unitOwnerExt = row.find('.matosUnitOwnerExt').val();
			var unitRemarque = encodeURIComponent(row.find('.matosUnitRemarque').val());
			
			if (unitRef.length == 0) {
				alert('Vous devez remplir tous les champs marqués d\'une étoile !');
				return;
			}
			if (unitRef == code) {
				alert('Les codes-barres doivent tous être différents !');
				return;
			}
			if ($.inArray(unitRef, matosUnitRefs) >= 0) {
				alert('Les codes-barres doivent tous être différents !');
				return;
			}
			matosUnitRefs.push(unitRef);
			
			ajaxStr += '&matosUnits['+unitIndex+'][ref]='+unitRef
					+'&matosUnits['+unitIndex+'][externe]='+unitExterne
					+'&matosUnits['+unitIndex+'][dateAchat]='+unitDateAchat
					+'&matosUnits['+unitIndex+'][ownerExt]='+unitOwnerExt
					+'&matosUnits['+unitIndex+'][remarque]='+unitRemarque;
			
			var champUnitId = row.find('.matosUnitId');
			if(champUnitId.length > 0) {
				ajaxStr += '&matosUnits['+unitIndex+'][action]=MOD'
						+'&matosUnits['+unitIndex+'][id]='+champUnitId.val();
			}
			else {
				ajaxStr += '&matosUnits['+unitIndex+'][action]=ADD'
			}
			
			unitIndex++;
		}
		
		var matosUnitsDelList = $('#modifieurPage .matosUnitDelId');
		for(var i = 0; i < matosUnitsDelList.length; i++) {
			ajaxStr += '&matosUnits['+unitIndex+'][action]=DEL'
					+'&matosUnits['+unitIndex+'][id]='+matosUnitsDelList.eq(i).val();
			unitIndex++;
		}
		
		AjaxFct(ajaxStr, 'matos_actions', false, 'retourAjax', 'matos_list_detail');
	});


	// Suppression d'un matos
	$('.deleteMatos').click(function () {
		var id = $(this).attr('id');
		var nom = $(this).attr('nom');
		var ajaxStr = 'action=delete&id='+id;
		if (confirm('Supprimer le matériel "'+nom+'" ? Sûr ??'))
			AjaxJson(ajaxStr, 'matos_actions', alerteErr);
	});

	// Si click sur matos externe, change l'info de date par "chez qui ?"
	$('.externeBox').click(function () {
		if ($(this).is(':checked')) {
			$('#dateAchatDiv').hide();
			$('#chezQuiDiv').show();
		}
		else {
			$('#dateAchatDiv').show();
			$('#chezQuiDiv').hide();
		}
	});


	// Ajout d'un matos
	$("#addMatos").click(function () {
		var label		= encodeURIComponent($('#newMatosLabel').val()) ;
		var ref			= $('#newMatosRef').val() ;
		var code		= $('#newMatosCode').val() ;
		var categ		= $('#newMatosCateg').val() ;
		var Souscateg	= $('#newMatosSousCateg').val() ;
		var Qtotale		= $('#newMatosQtotale').val() ;
		var dateAchat	= $('#newMatosDateAchat').val() ;
		var ownerExt	= $('#newMatosExtOwner').val() ;
		var tarifLoc	= $('#newMatosTarifLoc').val() ;
		var valRemp		= $('#newMatosValRemp').val() ;
		var remarque	= encodeURIComponent($('#newMatosRemark').val()) ;
		var externe		= ($('#newMatosExterne').is(':checked') ? 1 : 0);
		
		if (label.length == 0 || ref.length == 0 || categ.length == 0 || Qtotale.length == 0 || tarifLoc.length == 0 || valRemp.length == 0) {
			alert('Vous devez remplir tous les champs marqués d\'une étoile !');
			return;
		}
		if (! $.isNumeric(Qtotale) || Qtotale % 1 !== 0 || Qtotale <= 0) {
			alert('Quantité totale invalide !');
			return;
		}
		
		var ajaxStr = 'action=addMatos&label='+label+'&ref='+ref+'&codeBarres='+code
				 +'&categorie='+categ+'&sousCateg='+Souscateg
				 +'&Qtotale='+Qtotale+'&dateAchat='+dateAchat
				 +'&tarifLoc='+tarifLoc+'&valRemp='+valRemp
				 +'&externe='+externe+'&ownerExt='+ownerExt
				 +'&remarque='+remarque ;
		
		var matosUnitsList = $('table#newMatosListeUnits > tbody > tr');
		if (Qtotale < matosUnitsList.length) {
			alert('Quantité totale inférieure au nombre de matériel identifié unitairement !');
			return;
		}
		
		var matosUnitRefs = [];
		for(var i = 0; i < matosUnitsList.length; i++) {
			var row = matosUnitsList.eq(i);
			var unitRef = row.find('.matosUnitRef').val();
			var unitExterne = (row.find('.matosUnitExterne').is(':checked') ? 1 : 0);
			var unitDateAchat = row.find('.matosUnitDateAchat').val();
			var unitOwnerExt = row.find('.matosUnitOwnerExt').val();
			var unitRemarque = encodeURIComponent(row.find('.matosUnitRemarque').val());
			
			if (unitRef.length == 0) {
				alert('Vous devez remplir tous les champs marqués d\'une étoile !');
				return;
			}
			if (unitRef == code) {
				alert('Les codes-barres doivent tous être différents !');
				return;
			}
			if ($.inArray(unitRef, matosUnitRefs) >= 0) {
				alert('Les codes-barres doivent tous être différents !');
				return;
			}
			matosUnitRefs.push(unitRef);
			
			ajaxStr += '&matosUnits['+i+'][ref]='+unitRef
					+'&matosUnits['+i+'][externe]='+unitExterne
					+'&matosUnits['+i+'][dateAchat]='+unitDateAchat
					+'&matosUnits['+i+'][ownerExt]='+unitOwnerExt
					+'&matosUnits['+i+'][remarque]='+unitRemarque;
		}
		
		AjaxFct(ajaxStr, 'matos_actions', false, 'retourAjax', 'matos_list_detail');
	});

});


function refreshSousCatLine () {
	$('.sousCategLine').each(function(){
		if ($(this).next().attr('style') == 'display: none;')
			$(this).hide();
	});
}

/**
 * Callback qui met à jour l'affichage des champs de date d'achat et de prestataire externe pour un matériel unitaire après le clic sur la case à cocher correspondante.
 * 
 * Cette méthode doit être proxifiée pour que this corresponde au sélecteur JQuery de la ligne de tableau dont la case à cocher a été cliquée.
 * 
 * @param {JQuery.Event} eventObject - Évènement correspondant au clic.
 * @this JQuery
 */
function clickMatosUnitExterne(eventObject) {
	var dateAchatDiv = this.find('.matosUnitDateAchatDiv');
	var ownerExtDiv = this.find('.matosUnitOwnerExtDiv');
	if(this.find('input.matosUnitExterne').is(':checked')) {
		dateAchatDiv.hide();
		ownerExtDiv.show();
	}
	else {
		ownerExtDiv.hide();
		dateAchatDiv.show();
	}
}


/**
 * Supprime la ligne d'un matériel unitaire.
 * 
 * Cette méthode doit être proxifiée pour que this corresponde au sélecteur JQuery de la ligne de tableau du matériel unitaire à supprimer.
 * 
 * @param {boolean} isNew - Indique s'il s'agit du formulaire d'ajout (true) ou de modification (false) de matériel.
 * @this JQuery
 */
function clickMatosUnitDelete(isNew) {
	var isNewUnit = (isNew || this.find('.matosUnitId').length == 0);
	if(! confirm((isNewUnit ? 'Annuler l\'ajout du' : 'Supprimer le')+' matériel identifié "'+this.find('.matosUnitRef').val()+'" ?')) {
		return;
	}
	
	var unitId = this.find('.matosUnitId').val();
	this.remove();
	if(! isNewUnit) {
		$('#modMatosListeUnits').before('<input type="hidden" class="matosUnitDelId" value="'+unitId+'" />');
	}
	
	var champQuantite = isNew ? $('#newMatosQtotale') : $('#modMatosQteTot');
	var quantiteTotale = champQuantite.val();
	if($.isNumeric(quantiteTotale) && quantiteTotale % 1 === 0 && quantiteTotale > 1) // Propose de diminuer la quantité totale seulement si elle est valide et que ce n'était pas le dernier matériel
		if(confirm('Retrancher le matériel '+(isNewUnit ? 'annulé' : 'supprimé')+' de la quantité totale ?')) {
			var champQuantite = isNew ? $('#newMatosQtotale') : $('#modMatosQteTot');
			champQuantite.val(champQuantite.val() - 1);
		}
}


/**
 * Pré-remplit le formulaire de modification de matériel existant avec les données du matériel sélectionné.
 * 
 * @param {Object} data - Informations en JSON du matériel à afficher (y compris le matériel unitaire associé), retournées en AJAX par le serveur.
 */
function displaySelMatos(data) {
	$('#modMatosId').val(data.id);
	$('#modMatosRef').val(data.ref);
	$('#modMatosLabel').val(data.label);
	$('#modMatosCode').val(data.codeBarres);
	$('#modMatosQteTot').val(data.Qtotale);
	$('#modMatosTarif').val(data.tarifLoc);
	$('#modMatosValRemp').val(data.valRemp);
	$('#modMatosCateg').val(data.categorie);
	$('#modMatosSousCateg').val(data.sousCateg);
	$('#modMatosPanne').val(data.panne);
	$('#modMatosDateAchat').val(data.dateAchat);
	$('#modMatosExtOwner').val(data.ownerExt);
	$('#modMatosRem').val(data.remarque);
	if (data.externe == '1') {
		$('#modMatosExterne').attr('checked', 'checked');
		$('#dateAchatDiv').hide();
		$('#chezQuiDiv').show();
	}
	else {
		$('#modMatosExterne').removeAttr('checked');
		$('#chezQuiDiv').hide();
		$('#dateAchatDiv').show();
	}
	
	// Matériel identifié unitairement
	var listeUnits = $('table#modMatosListeUnits > tbody');
	listeUnits.empty();
	for(var i = 0; i < data.units.length; i++) {
		var unit = data.units[i];
		// Ajout d'une ligne au tableau
		var newRow = $('<tr class="ui-state-hover sousCategLine">\
				<td>\
					<input type="hidden" class="matosUnitId" />\
					<input type="text" class="matosUnitRef" size="15" />\
				</td>\
				<td><input type="checkbox" class="matosUnitExterne" /></td>\
				<td>\
					<div class="matosUnitDateAchatDiv">Acheté le : <input type="text" class="matosUnitDateAchat inputCal2" size="9" /></div>\
					<div class="matosUnitOwnerExtDiv">À louer chez : <input type="text" class="matosUnitOwnerExt" size="9" /></div>\
				</td>\
				<td><textarea class="matosUnitRemarque" cols="25" style="height: 18px; overflow-y: scroll;"></textarea></td>\
				<td><button class="bouton delUnitRow"><span class="ui-icon ui-icon-trash"></span></button></td>\
				</tr>');
		newRow.find('.matosUnitId').val(unit.id_matosunit);
		newRow.find('.matosUnitRef').val(unit.ref);
		newRow.find('.matosUnitDateAchat').val(unit.dateAchat);
		newRow.find('.matosUnitOwnerExt').val(unit.ownerExt);
		newRow.find('.matosUnitRemarque').val(unit.remarque);
		if(unit.externe == '1') {
			newRow.find('.matosUnitDateAchatDiv').hide();
			newRow.find('.matosUnitExterne').attr('checked', 'checked');
		}
		else {
			newRow.find('.matosUnitOwnerExtDiv').hide();
		}
		newRow.find('.inputCal2').datepicker({dateFormat: 'yy-mm-dd', firstDay: 1, changeMonth: true, changeYear: true});
		newRow.find('input.matosUnitExterne').click($.proxy(clickMatosUnitExterne, newRow));
		newRow.find('button.delUnitRow').button().click($.proxy(clickMatosUnitDelete, newRow, false));
		listeUnits.append(newRow);
	}
}


/**
 * Ajoute, dans le formulaire d'ajout ou de modification de matériel, une ligne supplémentaire dans le tableau du matériel identifié unitairement.
 * 
 * @param {boolean} isNew - Indique s'il s'agit du formulaire d'ajout (true) ou de modification (false) de matériel.
 */
function addMatosUnitRow(isNew) {
	isNew = (isNew === true); // Sécurité de typage
	// Gestion de la quantité totale
	var champQuantite = isNew ? $('input#newMatosQtotale') : $('input#modMatosQteTot');
	var quantiteTotale = champQuantite.val();
	var listeUnits = $((isNew ? 'table#newMatosListeUnits' : 'table#modMatosListeUnits') + ' > tbody');
	if(! (($.isNumeric(quantiteTotale) && quantiteTotale % 1 === 0 && quantiteTotale > 0) // La quantité de matériel doit être un entier positif
			|| (isNew === true && quantiteTotale.length == 0 && listeUnits.children().length == 0))) // Exception faite au début d'un ajout de matériel quand la quantité est vide (alors considérée comme 1) et que c'est la première fois qu'une ligne est ajoutée
	{
		alert('La quantité totale de matériel est incorrecte.');
		return;
	}
	if(quantiteTotale.length == 0) {
		quantiteTotale = 1;
		champQuantite.val(quantiteTotale);
	}
	else {
		quantiteTotale = parseInt(quantiteTotale, 10);
	}
	if(listeUnits.children().size() >= quantiteTotale) {
		if(! confirm('Tout le matériel est déjà identifié unitairement. Augmenter la quantité totale pour ajouter ce nouveau matériel ?')) {
			return;
		}
		champQuantite.val(++quantiteTotale);
	}
	
	// Ajout de la ligne
	var newRow = $('<tr class="ui-state-hover sousCategLine">\
			<td><input type="text" class="matosUnitRef" size="15" /></td>\
			<td><input type="checkbox" class="matosUnitExterne" /></td>\
			<td>\
				<div class="matosUnitDateAchatDiv">Acheté le : <input type="text" class="matosUnitDateAchat inputCal2" size="9" /></div>\
				<div class="matosUnitOwnerExtDiv">À louer chez : <input type="text" class="matosUnitOwnerExt" size="9" /></div>\
			</td>\
			<td><textarea class="matosUnitRemarque" cols="25" style="height: 18px;"></textarea></td>\
			<td><button class="bouton delUnitRow"><span class="ui-icon ui-icon-trash"></span></button></td>\
			</tr>');
	newRow.find('.matosUnitOwnerExtDiv').hide();
	newRow.find('.inputCal2').datepicker({dateFormat: 'yy-mm-dd', firstDay: 1, changeMonth: true, changeYear: true});
	newRow.find('input.matosUnitExterne').click($.proxy(clickMatosUnitExterne, newRow));
	newRow.find('button.delUnitRow').button().click($.proxy(clickMatosUnitDelete, newRow, isNew));
	listeUnits.append(newRow);
}


/////////////////////////////////////// OBSOLETE (sauf debug) ///////////////////////////////
function supprMatos (idMatos) {
	var AjaxStr = 'action=delete&id='+idMatos;
	if (confirm('Supprimer le matériel No '+idMatos+' ?'))
		AjaxFct(AjaxStr, 'matos_actions');
}
