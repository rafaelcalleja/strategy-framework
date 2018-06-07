{if isset($step)}
	<div class="vertical-step">
		<h1> Paso {$step+1}: {$reporte->getStepName($step+1)} </h1>

		{assign var="options" value=$reporte->getOptions($step+1)}
		<div style="float:right">
		{if count($options)}
			{foreach from=$options key=nombre item=campo}
				<div class="line-block">
					{include file=$tpldir|cat:'form/form_parts.inc.tpl'}
				</div>
			{/foreach}
		{else}
			<span style="margin-right: 10px;">
				<input type="radio" name="formato" value="sql" style="vertical-align:top"> <img src="{$resources}/img/famfam/script_code.png" style="vertical-align:text-top" /> SQL
				<input type="radio" name="formato" value="excel" style="vertical-align:top"> <img src="{$resources}/img/famfam/page_excel.png" style="vertical-align:text-top" /> Excel
				<input type="radio" name="formato" value="csv" style="vertical-align:top" checked> <img src="{$resources}/img/famfam/page_white_code.png" style="vertical-align:text-top" /> CSV  
			</span>
			<button class="btn" type="submit"><span><span>Descargar</span></span></button>
		{/if}
		</div>
	</div>
{else}
	<form action="{$smarty.server.REQUEST_URI}&send=1" target="async-frame" method="POST">
		<div class="vertical-step">
			<h1> Paso 1: Seleccionar modelo de informe </h1>
	
			<div style="float:right; display:inline;{if is_ie()}width: 150px;{/if}">
				<div class="select simple-select">
					<ul style="height: 21px; overflow:hidden;">
						<li><div><span class="arrow"></span>{$lang.titulo_seleccionar}</div></li>
						{foreach from=$reportes item=reporte}
							<li class="next-step" name="{$reporte->getUID()}"><div>{$reporte->getUserVisibleName()}</div></option>
						{/foreach}
					</ul>
				</div>
			</div>

		</div>
	</form>
	<div class="padded">
		<button class="btn refresh"><span><span> Reiniciar </span></span></button>
	</div>
{/if}
