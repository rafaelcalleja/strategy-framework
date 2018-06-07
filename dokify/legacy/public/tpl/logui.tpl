{*
Descripcion
	Convierte una lista de objetos LOGUI a html

En uso actualmente
	- /agd/logui.php

Variables
	· $collection - array( [logui] )
*}
<div id="logui">
{if count($collection)}
{foreach from=$collection item=log}
	{assign var=usuario value=$log->getUser()}
	<div class="log-entry {$log->getClass()}">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/text_align_center.png" alt="" />
		<div class="log-text">
			
			<strong>
				{if $usuario->exists()}
					{$lang.usuario}
					<a href="{$usuario->obtenerUrlFicha()}" class="box-it">{$usuario->getUserName()}</a>
				{else}
					
					{assign var=employee value=$log->getEmployee()}
					{if $employee->exists()}
						{$lang.empleado}
						<a href="{$employee->obtenerUrlFicha()}" class="box-it">{$employee->getUserName()}</a>
					{else}
						{$lang.usuario}  dokify
					{/if}
				{/if}
			</strong>
			realizó la siguiente acción: <strong>{$log->getText()}</strong>. {$lang.fecha} <strong>{$log->getDate($timezone)}</strong>

			{assign var=modified value=$log->getModifiedString($timezone, $usuario)}
			{if $modified}
				| {$modified}
			{/if}
		</div>
	</div>
{/foreach}
{else}
	<div style="text-align:center" class="padded">
		<div class="message highlight">{$lang.no_resultados}</div>
	</div>
{/if}
</div>
