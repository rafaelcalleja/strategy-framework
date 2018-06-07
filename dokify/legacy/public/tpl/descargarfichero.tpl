{*

	· $fichero - [Object fichero]
*}
<div class="box-title">
	{$lang.descargar_archivo}
</div>
<form name="descargar-fichero" action="{$smarty.server.PHP_SELF}" target="async-frame" id="descargar-fichero" enctype="multipart/form-data" method="POST">
	<div>
		<div class="cbox-content">
			{include file=$errorpath}
			{include file=$succespath}
			{include file=$infopath}
			<h1>{$fichero->getUserVisibleName("utf8_encode")}</h1>
			<table class="item-list">
				<tr>
					<td colspan="3">
						{$lang.descargar_texto}
						<br /><br />
					</td>
				</tr>
				{assign var="versiones" value=$fichero->getVersions()}
				{foreach from=$versiones item=version key=nombre}
					{if $version->size}
						<tr class="selected-row">
							<td>{$version->fecha}</td>
							<td>{$version->size}</td>
							<td><a href="{$smarty.server.PHP_SELF}?send=1&oid={$version->uid_fichero_archivo}&poid={$fichero->getUID()}" target="async-frame">Descargar</div></td>
						</tr>
					{/if}
				{/foreach}

			</table>
		</div>
	</div>


	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">


	</div>
</form>
