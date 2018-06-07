<form class="buscador-global">
	<table style="width: 100%" width="100%"><tr>
		<td style="width: 10%"> 
			{$lang.buscar} <span class="loading" style="display: none"><img src="http://estatico.afianza.net/img/common/ajax-loader.gif" /></span>
		</td>
		<td width="10px"> &nbsp; </td>
		<td> 
			<div>
				{if $user->isViewFilterByGroups()}<a style="float: right" class="toggle" target="#advanced-search" href=""> Busqueda avanzada</a>{/if}
				<input type="text" name="q" autocomplete="off" id="global-search-input" />
			</div>
		</td>
		<td style="width: 1%;"> &nbsp; </td>
	</tr>
	{if $user->isViewFilterByGroups()}
	{assign var="client" value=$user->getCompany()"}
	{assign var="dfields" value=$client->obtenerCamposDinamicos("agrupador")}

	<tr>
		<td colspan="4">
			<div id="advanced-search" style="display:none;">
				<h2>Busqueda Avanzada</h2>
				<table width="95%" style="table-layout: fixed">
					<tr>
						<td>Contiene</td><td><input type="text" name="" size="20" /></td>
						<td>Exacto</td><td><input type="text" name="equal" size="20" /></td>
					</tr>
					<tr>
						<td>Nombre Proyecto</td><td><input type="text" name="" size="20" /></td>
						<td>Manager</td><td><input type="text" name="manager" size="20" /></td>
					</tr>
					{foreach from=$dfields item=field key=i}
						{assign var="info" value=$field->getInfo()}
						{assign var="string" value=$info.nombre}
					{if ($i-1)%2}<tr>{/if}

						<td>{$lang.$string}</td><td><input type="text" name="{$string}" size="20" /></td>
					{if $i%2}</tr>{/if}
					{/foreach}

					<tr>
						<td colspan="4" style=""> <button class="btn" ><span><span>Buscar</span></span></button> </td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	{/if}
	</table>
</form>
