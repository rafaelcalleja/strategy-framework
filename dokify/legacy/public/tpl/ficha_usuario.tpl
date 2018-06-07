
<div class="box-title">
	{$lang.informacion_usuario}
</div>
<form name="ficha-usuario" action="{$smarty.server.PHP_SELF}" class="ficha form-to-box" id="ficha-usuario" method="GET">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div style="text-align: center">
		<div class="message highlight">
			
			<table style="table-layout: auto;">
				{assign var="uid" value=$usuario->getUID()}
				{assign var="datos" value=$usuario->getInfo(true)}
				{assign var="datos" value=$datos.$uid}
				{assign var="options" value=$usuario->getAvailableOptions($usuarioActivo,true)}
				{if count($options)<=count($datos)}
					{assign var="rowspan" value=$datos|@count}
				{else}
					{assign var="rowspan" value=$options|@count}
				{/if}
				<tr>
					<td colspan=2>
						{$lang.informacion}
						<br /><br />
					</td>
					<td rowspan="{$rowspan+3}" style="padding-left: 5px;">
						<ul>
							{if is_array($options) }
								{foreach from=$options item=option }
									{if $option.uid_accion != 10}
										{if $option.href[0] == "#"}
											{assign var="optionclass" value="unbox-it"}
										{else}
											{assign var="optionclass" value="box-it"}
										{/if}

										<li style="white-space: nowrap;" >
											<img style="vertical-align: middle;" src="{$option.img}" /> 
											<a href="{$option.href}" class="{$optionclass}">{$option.innerHTML}</a>
										</li>
									{/if}
								{/foreach}
							{/if}
						</ul>
					</td>
				</tr>
				{if count($datos)}
					{foreach from=$datos item=dato key=campo}
						<tr>
							<td style="width: 79px;">
								<span class="ucase">{$campo}</span>: 
							</td>
							<td style="width: 215px;">
								<strong class="ucase">{$dato}</strong>
							</td>
						</tr>
				{/foreach}
				{/if}
				{if $usuario->configValue("autologin") && $usuario->getUID() == $usuarioActivo->getUID()}
				<tr>
					<td colspan="2"><hr ></td>
				</tr>
				<tr>
					<td colspan="3">
						<a href="http://localhost/login.php?action=autologin&t={$usuario->getPublicToken('urlencode')}">Link de acceso público</a> &laquo; <span style="color: red">¡No compartas con nadie este link!</span>
					</td>
				</tr>
				{/if}
				{if isset($empresa)&&is_object($empresa)}
					{assign var="hermanos" value=$empresa->obtenerUsuarios()}
					{if (count($hermanos)>1) && $usuarioActivo->esAdministrador()}
						<tr>
							<td colspan="2"><hr ></td>
						</tr>
						<tr>
							<td colspan="3">
								<br />
								Utilizar permisos de este usario
								<select onchange="this.name='usuario-permisos';" style="width: 70%;">
									<option>Selecciona un usuario</option>
									{foreach from=$hermanos item=hermano key=campo}
										{if $hermano->getUID()!=$usuario->getUID()}
											<option value="{$hermano->getUID()}">{$hermano->getUserName()}</option>
										{/if}
									{/foreach}
								</select>
								<button class="btn"><span><span>Cambiar Permisos</span></span></button>
						
							</td>
						</tr>
					{/if}
				{/if}
			</table>
		</div>
	</div>	
	{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
	<input type="hidden" name="send" value="1" />
</form>
