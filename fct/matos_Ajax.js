
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
	$('.showMDtr').click(function() {
		$('.mDetail').hide();
		var idMatos = $(this).attr('id');
		$('#matosDetailTR-'+idMatos).toggle();
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
		var externe		= 0;
		if ($('#modMatosExterne').attr('checked')) externe	= 1 ;

		var AjaxStr = 'action=modif&id='+idMatos+'&label='+label+'&ref='+ref+'&codeBarres='+code
						+'&categorie='+categ+'&sousCateg='+sscateg
						+'&Qtotale='+Qtotale+'&dateAchat='+dateAchat
						+'&tarifLoc='+tarifLoc+'&valRemp='+valRemp+'&panne='+panne
						+'&externe='+externe+'&ownerExt='+ownerExt
						+'&remarque='+remarque ;
		AjaxFct(AjaxStr, 'matos_actions', false, 'retourAjax', 'matos_list_detail');
	});


	// Suppression d'un matos
	$('.deleteMatos').click(function () {
		var id = $(this).attr('id');
		var nom = $(this).attr('nom');
		var AjaxStr = 'action=delete&id='+id;
		if (confirm('Supprimer le matériel "'+nom+'" ? Sûr ??'))
			AjaxJson(AjaxStr, 'matos_actions', alerteErr);
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
		
		var matosUnitsList = $('table#listeMatosUnit > tbody > tr');
		var matosUnitsData = [];
		for(var i = 0; i < matosUnitsList.length; i++) {
			var row = matosUnitsList.eq(i);
			var unitRef = row.find('.newMatosUnitRef').val();
			var unitExterne = (row.find('.newMatosUnitExterne').is(':checked') ? 1 : 0);
			var unitDateAchat = row.find('.newMatosUnitDateAchat').val();
			var unitOwnerExt = row.find('.newMatosUnitOwnerExt').val();
			var unitRemarque = encodeURIComponent(row.find('.newMatosUnitRemarque').val());
			
			if (unitRef.length == 0) {
				alert('Vous devez remplir tous les champs marqués d\'une étoile !');
				return;
			}
			if (unitRef == code) {
				alert('Les codes-barres doivent tous être différents !');
				return;
			}
			for(var j = 0; j < matosUnitsData.length; j++) {
				if(unitRef == matosUnitsData[j].ref) {
					alert('Les codes-barres doivent tous être différents !');
					return;
				}
			}
			
			matosUnitsData.push({
					'ref' : unitRef,
					'externe' : unitExterne,
					'dateAchat' : unitDateAchat,
					'ownerExt' : unitOwnerExt,
					'remarque' : unitRemarque});
		}
		
		var strAjax = 'action=addMatos&label='+label+'&ref='+ref+'&codeBarres='+code
					 +'&categorie='+categ+'&sousCateg='+Souscateg
					 +'&Qtotale='+Qtotale+'&dateAchat='+dateAchat
					 +'&tarifLoc='+tarifLoc+'&valRemp='+valRemp
					 +'&externe='+externe+'&ownerExt='+ownerExt
					 +'&remarque='+remarque ;
		for (var i = 0; i < matosUnitsData.length; i++) {
			strAjax += '&matosUnits['+i+'][ref]='+matosUnitsData[i].ref
					+'&matosUnits['+i+'][externe]='+matosUnitsData[i].externe
					+'&matosUnits['+i+'][dateAchat]='+matosUnitsData[i].dateAchat
					+'&matosUnits['+i+'][ownerExt]='+matosUnitsData[i].ownerExt
					+'&matosUnits['+i+'][remarque]='+matosUnitsData[i].remarque;
		}
		AjaxFct(strAjax, 'matos_actions', false, 'retourAjax', 'matos_list_detail');
	});

});


function refreshSousCatLine () {
	$('.sousCategLine').each(function(){
		if ($(this).next().attr('style') == 'display: none;')
			$(this).hide();
	});
}


function displaySelMatos (data) {
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
}


/** Ajoute, dans le formulaire d'ajout de matériel, une ligne supplémentaire dans le tableau du matériel identifié unitairement. */
function addMatosUnitRow() {
	// Gestion de la quantité totale
	var quantiteTotale = $('input#newMatosQtotale').val();
	var listeUnits = $('table#listeMatosUnit > tbody');
	if(! (($.isNumeric(quantiteTotale) && quantiteTotale % 1 === 0 && quantiteTotale > 0) // La quantité de matériel doit être un entier positif
			|| (quantiteTotale.length == 0 && listeUnits.children().length == 0))) // Exception faite au début quand la quantité est vide (alors considérée comme 1) et que c'est la première fois qu'une ligne est ajoutée
	{
		alert('La quantité totale de matériel est incorrecte.');
		return;
	}
	if(quantiteTotale.length == 0) {
		quantiteTotale = 1;
		$('input#newMatosQtotale').val(quantiteTotale);
	}
	else {
		quantiteTotale = parseInt(quantiteTotale, 10);
	}
	if(listeUnits.children().size() >= quantiteTotale) {
		if(! confirm('Tout le matériel est déjà identifié unitairement. Augmenter la quantité totale pour ajouter ce nouveau matériel ?')) {
			return;
		}
		$('input#newMatosQtotale').val(++quantiteTotale);
	}
	
	// Ajout de la ligne
	var newRow = $('<tr class="ui-state-hover sousCategLine">\
			<td><input type="text" class="newMatosUnitRef" size="15" /></td>\
			<td><input type="checkbox" class="newMatosUnitExterne" /></td>\
			<td>\
				<div class="newMatosUnitDateAchatDiv">Acheté le : <input type="text" class="newMatosUnitDateAchat inputCal2" size="9" /></div>\
				<div class="newMatosUnitOwnerExtDiv">À louer chez : <input type="text" class="newMatosUnitOwnerExt" size="9" /></div>\
			</td>\
			<td><textarea class="newMatosUnitRemarque" cols="25" style="height: 18px;"></textarea></td>\
			<td><button class="bouton delUnitRow"><span class="ui-icon ui-icon-trash"></span></button></td>\
			</tr>');
	newRow.find(".inputCal2").datepicker({dateFormat: 'yy-mm-dd', firstDay: 1, changeMonth: true, changeYear: true});
	// Gestion de la case à cocher "externe ?"
	newRow.find('.newMatosUnitOwnerExtDiv').hide();
	newRow.find('input.newMatosUnitExterne').click($.proxy(function () {
				var dateAchatDiv = this.find('.newMatosUnitDateAchatDiv');
				var ownerExtDiv = this.find('.newMatosUnitOwnerExtDiv');
				if(this.find('input.newMatosUnitExterne').is(':checked')) {
					dateAchatDiv.hide();
					ownerExtDiv.show();
				}
				else {
					ownerExtDiv.hide();
					dateAchatDiv.show();
				}
			}, newRow));
	// Gestion du boutton de suppression de la ligne
	newRow.find('button.delUnitRow')
		.button()
		.click($.proxy(function() {
				if(! confirm('Supprimer le matériel identifié "'+this.find('.newMatosUnitRef').val()+'" ?')) {
					return;
				}
				this.remove();
				if(confirm('Retrancher le matériel supprimé de la quantité totale ?')) {
					var champQuantite = $('#newMatosQtotale');
					champQuantite.val(champQuantite.val() - 1);
				}
			}, newRow));
	listeUnits.append(newRow);
}


/////////////////////////////////////// OBSOLETE (sauf debug) ///////////////////////////////
function supprMatos (idMatos) {
	var AjaxStr = 'action=delete&id='+idMatos;
	if (confirm('Supprimer le matériel No '+idMatos+' ?'))
		AjaxFct(AjaxStr, 'matos_actions');
}
