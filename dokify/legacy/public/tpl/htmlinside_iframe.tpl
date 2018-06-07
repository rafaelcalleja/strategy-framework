{*
	UTILIZADA PARA DAR ESTILOS A IFRAME DENTRO DEL UN MODAL BOX

	· $data = array( "titulo columna | lang titulo columna" => "valor" );

*}
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" href="{$resources}/css/inc/class.css" rel="stylesheet" />
		<link type="text/css" href="{$resources}/css/inc/reset.css" rel="stylesheet" />
	</head>
	<body>
		<div style="margin: 10px">
			{if isset($data) && is_traversable($data)}
				{foreach from=$data key=key item=value}
					<div class="text-iframe">
						{if isset($results_prefix)}<strong>{$lang.$results_prefix}:</strong>{/if} {$value}
					</div>
				{/foreach}
			{else}
					<span class='text-iframe'>{$lang.$data}</span>
			{/if}
		</div>
	</body>
</html>
