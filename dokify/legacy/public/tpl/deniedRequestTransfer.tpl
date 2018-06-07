{*
Descripcion
	Se mostrara cuando un usuario intente descargar un zip desde una url publica

*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<link rel="shortcut icon" href="{$resources}/img/favicon.ico" />
		<link rel="icon" href="{$resources}/img/favicon.ico" />
		<link type="text/css" rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/external.css?{$smarty.const.VKEY}" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>dokify - {$lang.request_add_employee}</title>
	</head>
	<body>
		<div style="width:960px;margin:0 auto;">
			<div style="padding:20px 10px 25px;border-bottom:1px solid #F1F1F1;margin-bottom:10px">
				<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float:right" alt="dokify-logo" />
				<h1>{$lang.request_add_employee}</h1>
			</div>	
			<div style="float:left;width:100%;padding:10px">
				<h2>{$lang.solicitud_rechazada}</h2>
				<p>
					{assign var=solicitante value=$request->getSolicitante()}
					{$lang.denied_transfer_employee_message_employee|sprintf:$solicitante->getUserVisibleName()} 
				</p>
				<button style="margin-top:15px" onclick="location.href = 'https://dokify.net'" class="dokify">{$lang.go_web}</button>
			</div>
		</div>
	</body>
</html>
