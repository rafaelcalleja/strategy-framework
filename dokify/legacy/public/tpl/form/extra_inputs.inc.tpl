{if isset($campo.extra) }
	{if isset($campo.extraline) && $campo.extraline}<br />{/if}
	{if isset($multiples.$i)}
		{assign var="infomultiple" value=$multiples.$i}
	{/if}

	{foreach from=$campo.extra item=clave key=valor}
		{assign var=checked value=""}
		{if isset($clave.group) && (!isset($lastgroup) || $clave.group!=$lastgroup)}
			{if isset($lastgroup)}</div>{/if}
			{assign var="lastgroup" value=$clave.group}
			<div class="form-extra-fields"> {$lang.$lastgroup|default:$lastgroup}:
		{/if}


		{assign var="multiplename" value=$clave.name|cat:"["|cat:$i|cat:"]"}


		{if $clave.type == "checkbox" || $clave.type == "radio"}
			{*{if !is_numeric($i)} {assign var="i" value="0"} {/if}*}
			{assign var="multiplevalue" value=$clave.value}
		{else}
			{assign var="multiplevalue" value=$infomultiple[$clave.name]}
		{/if}


		{assign var="indicemultiple" value=$clave.name}
		{assign var="innerHTMLextra" value=$clave.innerHTML}


		{if $infomultiple[$indicemultiple] || $clave.type=='radio' && $multiplevalue==clave.value}
			{assign var=checked value="checked"}

			{if $clave.type == "radio" && $clave.value != $infomultiple.$indicemultiple}
				{assign var=checked value=""}
			{/if}
		{/if}


		<span style="white-space:nowrap"><{$clave.tag} type="{$clave.type}" {$checked} {if $clave.className}class="{$clave.className}"{/if} {if $clave.size}size="{$clave.size}"{/if} {if $clave.style}style="{$clave.style}"{/if} value="{$multiplevalue}" name="{$multiplename}" {if $clave.title}title="{$lang[$clave.title]}"{/if} /> {$lang.$innerHTMLextra|default:$innerHTMLextra}</span>
	{/foreach}

	{if isset($lastgroup)}</div>{assign var="lastgroup" value=null}{/if}
{/if}
