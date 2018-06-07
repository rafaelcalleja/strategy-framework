{*
Descripcion
	Para usar con el metodo addInfoLine de la clase jsonAGD y mostrar en la parte principal de la tabla datos del elemento que contiene el listado

En uso actualmente
	- carpeta/listado.php

Variables
	· $elemento - Objeto referencia

*}
{assign var="uid" value=$elemento->getUID()}
{assign var="datos" value=$elemento->getPublicFields(true,'ficha-tabla',$user)}
{assign var="options" value=$elemento->getAvailableOptions($user,true)}
	{if count($options)<=count($datos)}
		{assign var="rowspan" value=$datos|@count}
	{else}
		{assign var="rowspan" value=$options|@count}
	{/if}
	{*
				<tr>
					<td colspan=2><strong>{$lang.informacion}</strong>
					</td>
					<td rowspan="{$rowspan+3}" style="padding-left: 5px;">
						<ul>
							{if is_array($options) }
								{foreach from=$options item=option key=i }
									{if $option.uid_accion != 10}
										{if $option.href[0] == "#"}
											{assign var="optionclass" value="unbox-it"}
										{else}
											{assign var="optionclass" value="box-it"}
										{/if}

										<li style="white-space: nowrap;">
											<img style="vertical-align: middle;" src="{$option.img}" /> 
											<a href="{$option.href}" class="{$optionclass}">{$option.innerHTML}</a>
										</li>
									{/if}
								{/foreach}
							{/if}
						</ul>
					</td>
				</tr>
	*}
	<div class="ficha-tabla" style="text-align: left; padding: 5px;">
		{if count($datos)}
			{foreach from=$datos item=campo key=nombre}

				<div style="padding: 1px 0px;">
					<div class="ucase line-block" style="width: 120px;">
						{if isset($campo.name)}
							{assign var="name" value=$campo.name}
							{if isset($lang.$name)}
								{$lang.$name}
							{else}
								{$name}
							{/if}
						{elseif isset($lang.$nombre)}
							{$lang.$nombre}
						{else}
							{$nombre}
						{/if}:
					</div>
					<strong class="ucase">

						{if isset($campo.value)}
							{assign var="value" value=$campo.value}
							{if isset($campo.data)}
								{assign var="data" value=$campo.data}
								{reset array=$data result="dataexample"}

								{if is_object($dataexample)}
									{assign var="datatype" value=$dataexample->getType()}
									{if is_numeric($value)&&$value}
										{new type=$datatype uid=$value result="object"}
										{if $object->exists()}
											<a href="ficha.php?m={$datatype}&oid={$object->getUID()}" class="box-it">{$object->getUserVisibleName()}</a>
										{/if}
									{/if}
								{else}
									{$data.$value}
								{/if}
							{else}
								{$value}
							{/if}
						{elseif isset($campo.innerHTML)}
							{$campo.innerHTML}
						{/if}
					</strong>
				</div>
			{/foreach}
		{/if}
	</div>

