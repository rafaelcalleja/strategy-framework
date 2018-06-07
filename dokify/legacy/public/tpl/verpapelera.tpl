{*
	Listar los elementos desactivados en relacion a otro elemento...

	· $elementos - [Array] elementos que se mostraran como desactivados
	· $elemento - Objeto referente
*}
<div class="box-title">
	{$lang.ver_papelera}
</div>


{if count($elementos)}
	<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" method="post" id="elemento-form-papelera">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	{assign var=config value=$smarty.request.config}
	{if $modulo}
		{assign var=permisoRel value=$user->getAvailableOptionsForModule($modulo,29,$config)}
		{assign var=permisoElem value=$user->getAvailableOptionsForModule($modulo,14,$config)}
	{/if}

		<div class="cbox-content" style="max-height: 300px; overflow:auto;">
			{if $freeCompany && !$usuario->esStaff()}
			<div class="message error" style="margin-top:2px!important;">
				<h2>{$lang.expl_usuariopapelera}. <a href="/app/payment/license">{$lang.pincha_aqui}</a></h2>
			</div>
			{/if}

			{if isset($notify) && strlen($notify)}
				<div class="notifyAlert">
					{$notify}
				</div>
			{/if}
			
			<h1>{$lang.papelera} - {$elemento->getUserVisibleName()}</h1>
			{assign var=item value=$elemento}

			<table class="item-list">
				{foreach from=$elementos item=elemento}
					{assign var=nombreElemento value=$elemento->getUserVisibleName()}
					<tr>
						<td><label for="uid_{$elemento->getUID()}">
							{if ($elemento instanceof documento_atributo)}
								{assign var=solicitante value=$elemento->getElement($elemento)}
								{if $solicitante instanceof agrupador}
									{$solicitante->getHTMLDocumentName()}  &raquo; 
								{/if}
							{/if}
							{$nombreElemento}
						</label></td>
						<td style="text-align: right;"> 
							{if count($permisoRel)}
								{assign var=opcion value=$permisoRel.0}
								<a href="{$opcion.href}&poid={$smarty.request.poid}&oid={$elemento->getUID()}&config={$config}" class="box-it"><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_delete.png" style="vertical-align: middle" title="{$lang.eliminar_relacion}"/></a>
							{/if}
							{if count($permisoElem)}
								{assign var=opcion value=$permisoElem.0}
								<a href="{$opcion.href}&poid={$elemento->getUID()}&config={$config}" class="box-it"><img src="{$opcion.icono}" style="vertical-align: middle" title="{$lang.eliminar}"/></a>
							{/if}
							{*{assign var="key" value=""}{if $solicitante->referencia}{assign var="key" value="referencia-"|cat:$solicitante->referencia->getUID()}{/if}*}

							
							{if $elemento->isActivable($item, $user)}
								<input type="checkbox" class="line-check" id="uid_{$elemento->getUID()}" name="restaurar[]" value="{$elemento->getUID()}" /> 
							{else}
								{if $user->esStaff()}
									{assign var=empresas value=$elemento->getCompanies()}
									{assign var=intList value=$empresas->toIntList()}
									<a href="#buscar.php?p=0&q=tipo:empresa#{','|implode:$intList->getArrayCopy()}&all=1"><img src="{$resources}/img/famfam/find.png" title="{$lang.activo_otra_empresa} - Click para verlas" alt="lock" /></a>
								{/if}
								
								<a href="{$elemento->getType()}/asignarexistente.php?txt={$elemento->getUserVisibleName()}&oid={$elemento->getUID()}&poid={$smarty.request.poid}" class="box-it">
								<img src="{$resources}/img/famfam/arrow_refresh.png" title="{$lang.transferencia_empleado}" alt="lock" /></a>
							{/if}
							
						</td>
					</tr>
				{/foreach}
			</table>
		</div>
		<div class="cboxButtons">
			{if !$freeCompany || $usuario->esStaff() }
				<button class="btn checkall" target="form#elemento-form-papelera"><span><span> {$lang.seleccionar_todo} </span></span></button>
				<button class="btn{if isset($notifyConfirm)} confirm{/if}" {if isset($notifyConfirm) && strlen($notifyConfirm)}data-confirm="{$notifyConfirm}"{/if} type="submit" ><span><span> {$lang.restaurar} </span></span></button>
			{/if}
			<!-- <button class="btn delrel"><span><span> {$lang.eliminar_relacion} </span></span></button> -->
			{if count($permisoRel)}
				{assign var=opcion value=$permisoRel.0}
				<button class="detect-click btn confirm" type="submit" name="delrel" value="1"><span><span> {$lang.eliminar_relacion} </span></span></button>
			{/if}
			{if count($permisoElem)}
				{assign var=opcion value=$permisoElem.0}
				<button class="detect-click btn confirm" type="submit" name="delrel" value="1"><span><span> {$lang.eliminar} </span></span></button>
			{/if}
		</div>
		<input type="hidden" name="send" value="1" />
		<input type="hidden" name="o" value="{$smarty.request.o}" />
		<input type="hidden" name="poid" value="{$smarty.request.poid}" />
		<input type="hidden" name="m" value="{$modulo|default:$smarty.request.m}" />
		<input type="hidden" name="ref" value="{$smarty.request.ref}" />
	</form>
{else}
	<div style="text-align: center" class="cbox-content">
		<div class="message highlight" style="text-align: center;">
			{$lang.papelera_vacia}
		</div>
	</div>
{/if}

