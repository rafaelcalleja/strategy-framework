{if $campo.async && isset($campos)}
	<span class="async-info-load" href="../agd/singlefield.php?{$campo.async}">
		{if $campo.tag eq "select"}<select><option>{$lang.cargando}</option></select>{/if}
	</span>
{else}

	{if $campo.tag eq "input"}
		{if $campo.type eq "checkbox"}
			<input type="hidden" name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} {if isset($campo.value)}value="{$campo.value}"{/if}/><input type="{$campo.type}" data-nameDiv="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" data-nameTranslate="{$innerHTML}" class="{$campo.className|default:""}" onchange="var v=(this.checked)?1:0; $('input[name=\'{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}\']', this.parent).val(v);" {if isset($campo.onblur)}onblur="{$campo.onblur}"{/if} {if isset($campo.value) && $campo.value}checked{/if} {if isset($campo.disabled) && $campo.disabled}disabled{/if} {if isset($campo.dataConfirm)}data-confirm="{$campo.dataConfirm}"{/if} {if isset($campo.dataConfirmOnce)}data-confirm-once="{$campo.dataConfirmOnce}"{/if}
			{if isset($campo.incompatible)}
				data-incompatible="{','|implode:$campo.incompatible}"
			{/if} 
			{if isset($campo.attr)}
				{foreach from=$campo.attr item=valor key=clave}
					{$clave}="{$valor}"
				{/foreach}
			{/if}
			 />
		{else}
			{if strstr($nombre,'[]')}
				{if isset($campo.value)&&is_array($campo.value)}
					{if isset($campo.multiple)}
						{assign var="multiples" value=$campo.multiple}
					{/if}

					{foreach from=$campo.value item=valor key=i}
						<input type="{$campo.type}" class="{$campo.className|default:""} multiple" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} {if $campo.style}style="{$campo.style}"{/if} {if isset($campo.size)}size="{$campo.size}"{/if} rel="{if isset($campo.rel)}{$campo.rel}{elseif isset($campo.blank)&&($campo.blank===false)}blank{/if}" {if isset($campo.match)&&is_string($campo.match)}match="{$campo.match}"{/if} {if isset($campo.href)}href="{$campo.href}"{/if}  name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" {if isset($campo.onblur)}onkeyup="this.edited=true;" onblur="{$campo.onblur}"{/if} {if isset($campo.dataConfirm)}data-confirm="{$campo.dataConfirm}"{/if} value="{$valor}"/> 
						<a class="multiple" name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}">+</a>
						{if count($campo.value)>1}
							<a class="multiple rest" name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}">-</a>
						{/if}


						{include file=$tpldir|cat:'form/extra_inputs.inc.tpl'}

						{if $i!=count($campo.value)-1}
							</td></tr>
							<tr><td class="form-colum-description"> {$innerHTML} </td><td class="form-colum-separator"></td>
							<td class="form-colum-value" style="vertical-align: middle;">
						{/if}
					{/foreach}
				{else}
					<input type="{$campo.type}" class="{$campo.className|default:""} multiple" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} {if isset($campo.size)}size="{$campo.size}"{/if} rel="{if isset($campo.rel)}{$campo.rel}{elseif isset($campo.blank)&&($campo.blank===false)}blank{/if}" {if isset($campo.match)&&is_string($campo.match)}match="{$campo.match}"{/if} {if isset($campo.href)}href="{$campo.href}"{/if}  name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" {if isset($campo.onblur)}onkeyup="this.edited=true;" onblur="{$campo.onblur}"{/if} {if isset($campo.dataConfirm)}data-confirm="{$campo.dataConfirm}"{/if} {if isset($campo.dataConfirm)}data-confirm="{$campo.dataConfirm}"{/if} /> 
					<a class="btn multiple" name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}">+</a>

					{include file=$tpldir|cat:'form/extra_inputs.inc.tpl'}
				{/if}
			{else}
		
				{if isset($campo.value)&&(trim($campo.value)||is_numeric($campo.value))} 
					{if (isset($campo.date_format) && ($campo.value)==0)}
						{assign var=val value=""}
					{else}
						{assign var=val value=$campo.value}
					{/if}
				{else}
					{if $campo.default}
						{assign var=val value=$campo.default}
					{else}
						{assign var=val value=""}
					{/if}
				{/if}

				<input type="{$campo.type}" class="{$campo.className|default:""}" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.placeholder)}placeholder="{$campo.placeholder}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} {if isset($campo.disabled) && $campo.disabled==true}disabled{/if} {if isset($campo.size)}size="{$campo.size}"{/if} rel="{if isset($campo.rel)}{$campo.rel}{elseif isset($campo.blank)&&($campo.blank===false)}blank{/if}" {if isset($campo.match)&&is_string($campo.match)}match="{$campo.match}"{/if} {if isset($campo.href)}href="{$campo.href}"{/if}  name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" {if isset($campo.onblur)}onkeyup="this.edited=true;" onblur="{$campo.onblur}"{/if} {if isset($campo.dataConfirm)}data-confirm="{$campo.dataConfirm}"{/if} value="{$val}" /> 	
				{if isset($campo.extra) }
					{foreach from=$campo.extra item=clave key=valor}
						{assign var="innerHTML" value=$clave.innerHTML}
						<{$clave.tag} type="{$clave.type}" value="{$clave.value}" name="{$clave.name}" /> {if (isset($clave.innerHTML)) }{$lang.$innerHTML}{/if}
					{/foreach}
				{/if}
			{/if}
		{/if}
	{elseif $campo.tag eq "textarea"}
		<textarea name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" placeholder="{if isset($campo.placeholder)}{$lang[$campo.placeholder]}{/if}" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} class="{$campo.className|default:""}" rel="{if isset($campo.blank)&&($campo.blank===false)}blank{/if}"  {if isset($campo.maxlength)}maxlength="{$campo.maxlength}"{/if} {if isset($campo.rows)}rows="{$campo.rows}"{/if} style="resize:none">{if isset($campo.value)}{$campo.value}{/if}</textarea>
	{elseif $campo.tag eq "span"}
		<span style="line-height: 2em;" class="{$campo.className|default:""}" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} >
		{if isset($campo.value)}
			{assign var="value" value=$campo.value}
			{if isset($campo.divide)}
				{$lang.$value}
			{elseif isset($campo.data) && ( is_array($campo.data) || $campo.data instanceof ArrayObject )}
				{$campo.data[$value]}
			{else}
				{$value}
			{/if}
		{/if}
		</span>
	{elseif $campo.tag eq "a"}
		{assign var=val value=""}
		{if isset($campo.value)&&trim($campo.value)} 
			{assign var=val value=$campo.value}
		{else}
			{if $campo.default}
				{assign var=val value=$campo.default}
				{if $campo.format}
					{assign var=val value=$campo.format|sprintf:$val}
				{/if}
			{/if}
		{/if}
		<a style="line-height: 2em;" class="{$campo.className|default:""}" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" {if isset($campo.href)}href="{$campo.href}"{/if}>{$val}</a>
	{elseif $campo.tag eq "slider"}
		{assign var=value value=$campo.value|default:0}
		{if !$value && $campo.default}
			{assign var=value value=$campo.default}
		{/if}

		<div><div style="line-height: 2em;" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{/if} class="{$campo.className|default:""} slider line-block" {if isset($campo.count)}count="{$campo.count}"{/if} {if isset($campo.divide)}divide="{$campo.divide}"{/if} value="{$value}"></div><input type="text" name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" class="slider-value" value="{$value}"></div></div>
	{elseif $campo.tag eq "select"}
		{if $campo.search}
			<input type="hidden" /><input type="text" value="{$lang.buscar} {$innerHTML}" onfocus="if(this.value=='{$lang.buscar} {$innerHTML}')this.value='';" onblur="if(!this.value)this.value='{$lang.buscar} {$innerHTML}';" class="find-html" rel="option" target="#{$campo.id|default:$nombre|replace:'[]':''}"/>
		{/if}

		{assign var=total value=$campo.data|@count}
		<select name="{if isset($campo.name)}{$campo.name}{else}{$nombre}{/if}" class="{$campo.className|default:''}" {if strstr($nombre,'[]')}multiple="true" style="max-height:120px; height: {math equation="x*y" x=$total y=1.4}em;"{/if} {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.id)}id="{$campo.id}"{elseif $campo.search}id="{$nombre|replace:'[]':''}"{/if} {if isset($campo.blank)&&($campo.blank===false)}rel="blank"{/if}>
			{if isset($campo.data) && is_traversable($campo.data) }
				{if isset($campo.default)}
					{assign var=string value=$campo.default}
					<option value="">{$lang.$string|default:$string}</option>
				{/if}

				{foreach from=$campo.data item=nombrevalor key=valor}
					{if is_object($nombrevalor)}
						<option value="{$nombrevalor->getUID()}" {if isset($campo.value)&&$campo.value==$nombrevalor->getUID()}selected{/if} {if strstr($nombre,'[]')&&@in_array($nombrevalor->getUID(),$campo.value)}selected{/if} >{$nombrevalor->getSelectName()}</option>											
					{elseif is_array($nombrevalor)}
						<option {if isset($nombrevalor.value)}value="{$nombrevalor.value}"{/if} {if isset($nombrevalor.name)}name="{$nombrevalor.name}"{/if} {if isset($campo.value)&&$campo.value==$valor}selected{/if} {if strstr($nombre,'[]')&&@in_array($valor,$campo.value)}selected{/if}  {if $nombrevalor.className}class="{$nombrevalor.className}"{/if}>{if isset($lang[$nombrevalor.innerHTML])}{$lang[$nombrevalor.innerHTML]}{else}{$nombrevalor.innerHTML}{/if}</option>
					{else}
						<option value="{$valor}" {if isset($campo.value)&&$campo.value==$valor}selected{/if} {if strstr($nombre,'[]')&& (@in_array((string)$valor,$campo.value,true) || @in_array((int)$valor,$campo.value,true))}selected{/if}>{if isset($lang.$nombrevalor)}{$lang.$nombrevalor}{else}{$nombrevalor}{/if}</option>
					{/if}
				{/foreach}

				{if isset($campo.others) && $campo.others == true}
					<option class="add-option">{$lang.anadir_otro_valor}</option>
				{/if}

			{/if}
		</select>
	
	{elseif $campo.tag eq "button"}
		<button class="btn {$campo.className|default:''}"><span><span>{$campo->getInnerHTML($nombre)}</span></span></button>
	{/if}

	{if $nombre eq "pass"}
		</td></tr> {* PARA CUADRAR EN FORM .tpl *}
		<tr>
			<td class="form-colum-description"> {$lang.repite_pass} </td>
			<td class="form-colum-separator"></td>
			<td>
				<input type="{$campo.type}" name="{$nombre}2" {if isset($campo.target)}target="{$campo.target}"{/if} {if isset($campo.onblur)} onblur="{$campo.onblur}" {/if} {if isset($campo.id)}id="{$campo.id}"{/if} /> 
		
	{/if}
{/if}
