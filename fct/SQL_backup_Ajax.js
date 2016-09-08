
function addUploadedFile( fileName ) {
	var list = $('#importList');
	var fileExists = false;
	list.find('option').each(function() {
		if (this.value == fileName) {
			fileExists = true;
			return false;
		}
	});
	if(! fileExists ) {
		list.append($('<option>', {value: fileName, text: fileName}));
	}
}

$(document).ready(function() {
	$('#dumpSQL').click(function() {
		tableToSave = $('#tableList').val();
		if (confirm('Sauvegarder la base de donnée ?'))
			AjaxFct ('dump='+tableToSave, 'SQL_backup', false, 'retourAjax');
	});
	
	$('#restoreSQL').click(function() {
		fileBackup = $('#dumpList').val();
		if (fileBackup != '----') {
			if (confirm('ATTENTION ! Vous allez effectuer une restauration de la base de données avec le fichier :\r\n'+fileBackup+'\r\nCette action est irréversible !\r\n \r\nCONTINUER ?'))
				AjaxFct ('restore=all&fileBackup='+fileBackup, 'SQL_backup', false, 'retourAjax');
		}
		else alert('Merci de choisir un fichier !');
	});
	
	$('#downloadSQL').click(function() {
		fileBackup = $('#dumpList').val();
		if (fileBackup != '----') {
			window.open ('fct/downloader.php?dir=sql&file=' + fileBackup );
		}
		else alert('Merci de choisir un fichier !');
	});
	
	$('#importInventaire').click(function() {
		fileBackup = $('#importList').val();
		if (fileBackup != '----') {
			if (confirm('ATTENTION ! Vous allez effectuer une importation d\'inventaire dans la base de données avec le fichier :\r\n'+fileBackup+'\r\nToute modification de matériel existant est irréversible !\r\n \r\nCONTINUER ?'))
				AjaxFct ('import=matos&fileBackup='+fileBackup, 'SQL_backup', false, 'retourAjax');
		}
		else alert('Merci de choisir un fichier !');
	});
	
	// #uploadInventaire
	uploader = new qq.FileUploader({
		element: document.getElementById('uploadInventaire'),
		action: 'fct/uploader.php?dataType=inventaire&folder=',
		debug: false,
		sizeLimit: 62914560,
		minSizeLimit: 0,
		allowedExtensions: ['csv'],
		onComplete: function(id, fileName, responseJSON){
				$(".qq-upload-success > .qq-upload-file").each( function(ind, obj) {
						var name = $(this).html();
						name = name.replace(/&amp;/g, '&');
						if ( name == fileName )
							addUploadedFile( fileName );
						$(obj).parent('.uploading_file').remove();
					});
			},
		onProgress: function(id, fileName, loaded, total){
				var percent = parseInt ( loaded * 100 / total ) ;
				$( ".progressbar[id$='prog_"+id+"']" ).progressbar({value: percent});
			},
		template: '<div class="qq-uploader">' +
					'<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' +
					'<div class="qq-upload-button"><button class="bouton">DÉPOSER UN FICHIER</button></div>' +
					'<ul class="qq-upload-list"></ul>' + 
				'</div>',
		fileTemplate: '<div class="uploading_file">' +
					'<span class="qq-upload-file"></span>' +
					'<span class="qq-upload-spinner" style="width:150px;"><div class="progressbar" style="height:13px;" ></div></span>' +
					'<span class="qq-upload-size"></span>' +
					'<a class="qq-upload-cancel" href="#">Annuler</a> ' +
					'<span class="qq-upload-failed-text">Erreur</span>' +
				'</div>',
		messages: {
			typeError: "{file} Extension de fichier non permise. Utilisez des fichiers : {extensions}.",
			sizeError: "{file} : Taille de fichier limité à {sizeLimit}.",
			minSizeError: "{file} est trop petit, la taille minimum est de {minSizeLimit}.",
			emptyError: "{file} est vide !",
			onLeave: "Des fichiers sont en cours de téléchargement. Si vous quittez la page, ils seront corrompus !"
		}
	});
	$('.qq-upload-button .bouton').button();
});

function attachProgressBar( id, fileName ) {
	$(".qq-upload-file").each( function( ind, obj ) {
		if( $(obj).html() == fileName ) {
			$(obj).parent('.uploading_file').find('.progressbar').attr('id', 'prog_'+id ) ;
		}
	});
}

