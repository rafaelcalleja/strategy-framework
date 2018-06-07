{*
Descripcion
	Se mostrara cuando un usuario intente descargar un zip desde una url publica

*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<link rel="shortcut icon" href="{$resources}/img/favicon.ico" />
		<link rel="icon" href="{$resources}/img/favicon.ico" />
		<link rel="stylesheet" href="{$resources}/css/inc/reset.css" type="text/css" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>dokify - Download File</title>
		{literal}<style>
			#downloadblock{ width: 400px; margin: 0 auto; text-align: center; font-size: 18px; }
		</style>{/literal}
	</head>
	<body>
		<div style="margin: 50px 0; text-align: center" id="logo">
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png"/> 
		</div>
		<div id="downloadblock">
			<span id="statustext">{$lang.descargando_fichero}...</span>
		</div>
		{literal}
			<script>	
				window.setTimeout(function(){
					var frame = document.getElementById("fileframe").src = '{/literal}{$url}{literal}'
				}, 1000);
			</script>
		{/literal}
		<iframe style="display: none" id="fileframe"></iframe>
	</body>
</html>
