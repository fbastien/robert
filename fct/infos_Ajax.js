
$(function () {
	$('#saveInfos').click(function () {
		var ajaxReq = 'action=modifConsts'
		$('#infosDiv input').each(function() {
			var dataType = $(this).attr('name');
			var dataVal = $(this).val();
			if (dataType == 'boite.TVA.val') dataVal /= 100;
			ajaxReq += '&'+dataType+'='+dataVal ;
		});
		AjaxFct(ajaxReq, 'infos_actions', false, 'retourAjax');
	});
});