<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<script type="text/javascript" src="{$resources}/js/tiny_mce/tiny_mce_popup.js"></script>
	<script type="text/javascript" src="{$resources}/js/tiny_mce/plugins/strings/js/emotions.js"></script>
</head>
<body style="display: none" role="application" aria-labelledby="app_title">
	<div class="box-title">
		{$lang.variables_predefinidas}
	</div>
	<div class="cbox-content" style="width: 550px;">
		<table style="font-size:12px">
			<thead style="text-align: left;">
				<tr>
					<th>Descripcion</th>
					<th>Valor</th>
					<th>Multi-idioma</th>
				</tr>
			</thead>
			{foreach from=$data item=infoString}

				<tr>
					<td style="padding: 1px 3px;">{$infoString.descripcion}</td>
					<td style="padding: 1px 6px;"><a href="javascript:EmotionsDialog.insert('{$infoString.value}');" >{if print(htmlentities($infoString.value))}{/if}</a></td>
					<td style="padding: 1px 3px;">{if $infoString.translate}Si{/if}</td>
				</tr>
			{/foreach}
		</table>
	</div>
</body>
