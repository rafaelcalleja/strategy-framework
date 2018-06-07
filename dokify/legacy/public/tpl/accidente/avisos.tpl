{*
	en uso por /agd/accidente/avisos.php
*}
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" href="{$resources}/css/inc/class.css" rel="stylesheet" />
	</head>
	<body>
		<form name="elemento-form-new" 
		action="{$smarty.server.PHP_SELF}" 
		class="form-to-box asistente" 
		method="{$smarty.server.REQUEST_METHOD}" 
		id="elemento-form-new" {if isset($width)}style="width: {$width};"{/if}>
			<label for="otrosdestinatarios">{$lang.destinatarios_adicionales}</label><br />
			<input type="text" name="otrosdestinatarios" style="width: 100%;"/><br />
			<input type="hidden" name="send" value="1" />
			<input type="hidden" name="aviso" value="{$aviso}" />
			<input type="hidden" name="oid" value="{$oid}" id="oid" />
			<input type="submit" class="btn" value="enviar"></form>	
	</body>
</html>
