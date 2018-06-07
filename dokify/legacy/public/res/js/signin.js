(function(window, undefined){

	$("#uid_pais").change(function(){
		if ($(this).val() == $(this).data('defaultcountry')){
			$($(this).data('reference')).show();
		}else{
			$($(this).data('reference')).hide();
		}
	});

	$("#uid_provincia").change(function(){

		$("#loading-ajax").show();
		var divRefereneContainer = $(this).data('reference');
		var divRefreshSelect = $(this).data('refresh');

		$.get("/agd/empresa/new.php?m=municipio&poid="+$(this).val())
			.success(function(data){

				$(divRefereneContainer).html(data);
				if (!$.browser.msie){
					$(divRefreshSelect).chosen();
				}
					
				$("#loading-ajax").hide();
			}).error(function() {
	 	  		$("#loading-ajax").hide();
	 	  		$("#loading-ajax-error").show();
			});
	});

	$("#pass").focus(function(){
		$(".strength").show();
	});

	$("input").focus(function(){
		$(this).removeClass("error");
	});
	
	var validatePassword, matchPassword, debil = $('#pass').data("debil"), aceptable = $('#pass').data("aceptable"), fuerte = $('#pass').data("fuerte");
	$('#passCont').pschecker({ onPasswordValidate: validatePassword, onPasswordMatch: matchPassword, debil : debil, aceptable : aceptable, fuerte : fuerte });

	if (!$.browser.msie){
		$(".chzn-select").chosen();
		$(".chzn-select-deselect").chosen({allow_single_deselect:true});
	}

})(window);