{assign var="uid" value=$elemento->getUID()}
<div id="item-profile">
	<h1> 
		{$elemento->getUserVisibleName()}
	</h1>
	<div id="profile-column-1" class="profile-column">
		<div>
			{assign var="datos" value=$elemento->getInfo(true, "ficha", $user)}
			{assign var="datos" value=$datos.$uid}

			<table style="width: 100%" cellpadding="0">
			{foreach from=$datos item=dato key=campo}
				{if $dato}
					<tr>
						<td style="width: 100px;">
							<span class="ucase">{if isset($lang.$campo)}{$lang.$campo}{else}{$campo}{/if}</span>: 
						</td>
						<td style="width: 235px;">
							<strong class="ucase">
								{if is_string($dato)}
									{$dato}
								{elseif isset($dato.innerHTML)}
									{$dato.innerHTML}
								{/if}
							</strong>
						</td>
					</tr>
				{/if}
			{/foreach}
			</table>

			{if method_exists($elemento, "getRelatedData") && $data = $elemento->getRelatedData($user)}
				{if count($data)}
					<hr />
					<div>
						<ul>
						{foreach from=$data item=dom}
							<li>{$dom.innerHTML}</li>
						{/foreach}
						</ul>
					</div>
				{/if}
			{/if}

			{if method_exists($elemento, "getSimilars") && $similares = $elemento->getSimilars(10)}
				{if count($similares)}
					<hr />
					<div>
						<h3>Similares:</h3>
						<ul>
						{foreach from=$similares item=similar}
							<li>{$similar->obtenerUrlPerfil($similar->getUserVisibleName())}</li>
						{/foreach}
						</ul>
					</div>
				{/if}
			{/if}
		</div>
		{if $elemento instanceof agrupador && $elemento->esFilter() && $address = trim($elemento->obtenerDato('direccion'))}
			<div class="padded">
				<div class="map" style="width:100%;height:300px;" data-address="{$address}" data-streetview="false" data-types="[]"></div>
			</div>
		{/if}
	</div>
	<div id="profile-column-2" class="profile-column">
		<div>
			<div>
				<h3> Actividad y mensajes </h3>
				<hr />
				{if $messages = $elemento->getExceptionMessages($user)}
					{foreach from=$messages item=message}
						<div class="message error" style="margin: 5px; width: 95%;">{$message->getMessage()}</div>
					{/foreach}
				{else}
					<div class="message highlight">No hay ningún mensaje</div>
				{/if}
			</div>
		</div>	
	</div>
	<div id="profile-column-3" class="profile-column">
		<div>
			{assign var="options" value=$elemento->getAvailableOptions($user,true,0,false)}
			<ul class="item-options">
				{if is_traversable($options)}
					{foreach from=$options item=option key=i}
						{if $option.uid_accion != 10}
							{if $option.href[0] == "#"}
								{assign var="optionclass" value="unbox-it"}
							{else}
								{assign var="optionclass" value="box-it"}
							{/if}

							<li style="white-space: nowrap;">
								<img style="vertical-align: middle;" src="{$option.img}" height="16px" width="16px"/> 
								<a href="{$option.href}" class="{$optionclass}">{$option.innerHTML}</a>
							</li>
						{/if}
					{/foreach}
				{/if}
			</ul>
		</div>
	</div>
	<div class="clear"></div>
</div>
