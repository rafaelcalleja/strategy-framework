{*
Descripcion
	Solo para mostrar informes...

En uso actualmente
	-	/agd/informes.php

Variables
	· $informes - array( Objecto Informe  )
*}	
<div class="box-title">
	Informes
</div>
<form name="informes-empresa" action="{$smarty.server.PHP_SELF}" id="informes-empresa" class="form-to-box" enctype="multipart/form-data" method="POST">
	<div class="cbox-content">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}

		{if isset($exportaciones) && count($exportaciones)}
			<h2> {$lang.exportaciones} </h2>
			<table class="item-list">
				{foreach from=$exportaciones item=exportacion}
					<tr>
						<td> {$exportacion->getUserVisibleName()} </td>
						<td>
							<a href="informes.php?poid={$smarty.get.poid}&m={$exportacion->getType()}&name={$exportacion->getName()}&action=export&send=1" target="async-frame">{$lang.descargar}</a>						
						</td>
					</tr>
				{/foreach}
			</table>
			<br /><br />
		{/if}

		<h2> {$lang.informes} </h2>
		<table class="item-list">
			{foreach from=$informes item=informe}
				{assign var="estado" value=$informe->getLoadStatus()}
				<tr>
					<td> {$informe->getUserVisibleName()} </td>
					<td> {$informe->getLoadStatus(true)} </td>
					<td >
						{if $estado}<a href="informes.php?poid={$smarty.get.poid}&m={$smarty.get.m}&oid={$informe->getUID()}&action=dl&send=1" target="async-frame">{/if}{$lang.descargar}{if $estado}</a>{/if}
						{if $user->esStaff()}
							| <a class="box-it" href="informes.php?poid={$smarty.get.poid}&m={$smarty.get.m}&oid={$informe->getUID()}&action=rm&send=1" >{$lang.eliminar}</a>
						{/if}
					</td>
				</tr>
			{/foreach}
		</table>
		{if $user->esStaff()}
		<br />
		<hr />
		<div>
			<div id="upload-info" style="float: right;">
				
			</div>
			<div>
				Selecciona el archivo a cargar: <br />
				<div class="filecontainer line-block">
					<button class="btn" style="white-space: nowrap"><span><span>Examinar...</span></span></button><input type="file" name="archivo" target="#upload-info"/>
				</div>
				<button class="btn" type="submit"><span><span>{$lang.enviar}</span></span></button>
			</div>
		</div>
		{/if}
	</div>
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">

	</div>
</form>

