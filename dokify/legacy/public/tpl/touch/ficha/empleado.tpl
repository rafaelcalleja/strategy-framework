{assign var="uid" value=$elemento->getUID()}
{assign var="datos" value=$elemento->getInfo(true, "ficha", $user)}
{assign var="datos" value=$datos.$uid}
{if isset($papelera)&&$papelera}
	<div class="bad-profile"> {$lang.elemento_actualmente_papelera} </div>
{/if}
<div class="cbox-content">
	{if isset($qr) && $leaving}
		<div class="message succes" style="margin-bottom: 2em; display:block;">
			{$lang.place_leave_registered}
		</div>
	{/if}

	{if isset($avisosestado)}
		{foreach from=$avisosestado item=aviso}
			{assign var="string" value=$aviso.string}
			{if (isset($string))}
				<div class="{$aviso.class}" style="text-align: center">
					{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}
				</div>
			{/if}
		{/foreach}
	{/if}
	<br />
	<div style="float:right;">
		<img style="vertical-align: middle;" class="emp-photo" src="../agd/empleado/foto.php?poid={$elemento->getUID()}&t={$time}" width="73">
	</div>
	<div>
		<table style="width:auto" class="profile">
			<tr>
				<td class="first"><span class="ucase">{$lang.dni}</span>: </td>
				<td >
					<strong class="ucase">{$datos.dni}</strong>
				</td>
			</tr>
			<tr>
				<td class="first"><span class="ucase">{$lang.nombre}</span>: </td>
				<td>
					<strong class="ucase">{$datos.nombre}</strong>
				</td>
			</tr>
			<tr>
				<td class="first"><span class="ucase">{$lang.apellidos}</span>: </td>
				<td>
					<strong class="ucase">{$datos.apellidos}</strong>
				</td>
			</tr>
		</table>

		{if $responsable = $elemento->getManager()}
			<table style="width:auto" class="profile">
				<tr>
					<td class="first">
						<span class="ucase">{$lang.responsable|default:"Responsable"}</span>: 
					</td>
					<td>
						<strong class="ucase">
							{$responsable->obtenerURLFicha($responsable->getUserVisibleName())}
						</strong>
					</td>
				</tr>
			</table>	
		{/if}
			
	</div>
	<div style="clear:both"></div>
	<div>
		<hr />
		<div style="margin-bottom:0.5em">
			<strong>{$lang.empresas}</strong>
		</div>
		{foreach from=$empresas item=empresa}
			{assign var="status" value=$elemento->getStatusInCompany($user, $empresa, true)}

			{assign var="miniEmpresa" value=$empresa->getMiniArray($user)}
			<div class="elemento-status-bar elemento-status-{$status.class}" style="margin-bottom:5px">
				<img style="vertical-align: middle; margin-bottom: 2px;" src="{$miniEmpresa.estado.src}" height="12" width="12">
				
				{$empresa->getUserVisibleName()}
			</div>
		{/foreach}					
	</div>

	<div style="clear:both"></div>		
	{assign var="userCompany" value=$user->getCompany()}
	{assign var="unsuitableItemCompanies" value=$userCompany->getUnsuitableItemClient($elemento)}
	{if count($unsuitableItemCompanies)}
		<div>
			<hr />
			<div style="margin-bottom:0.5em"><strong>{$lang.empleado_no_apto_touch}</strong></div>
			<table style="width:auto">	
				<tr>
					<td>
						{foreach from=$unsuitableItemCompanies item=company}
							<li>
								{$company->getUserVisibleName()}
							</li>
						{/foreach}
					</td>
				</tr>
			</table>
		</div>
	{/if}	


	{if $showQRButtons}
		<hr style="margin-top:1em" />
		<div style="text-align: center; margin-top: 1em" id="checkin-area">
			{if $validCompanies > 0}
				<button class="button green xl async" data-href="empleado/location.php?poid={$elemento->getUID()}&amp;action=checkin" data-target="#checkin-area" style="width:100%">
					<strong>{$lang.place_access_register}</strong>
				</button>		
			{else}
				<button class="button red xl" onclick="return false;" style="width:100%; padding-left: 5px; padding-right: 5px;">
					<strong>{$lang.place_access_blocked}</strong>
				</button>
			{/if}
		</div>
	{/if}

	{if $retryQR}
		<hr style="margin-top:1em" />
		<div class="message succes" style="margin-bottom: 2em; display:block;">
			{$lang.place_already_registered}
		</div>
	{/if}
</div>