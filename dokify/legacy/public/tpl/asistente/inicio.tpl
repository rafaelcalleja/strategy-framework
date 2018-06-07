<div class="box-title">
	{$lang.primeros_pasos}
</div>
<div class="cbox-content" style="width: 100%">
	{include file=$tpldir|cat:'asistente/inicio/steps_'|cat:$locale|cat:'.inc.tpl'}
</div>
<div class="cboxButtons">
	<div style="float:left">
	{if $smarty.request.step != -1}
		<button class="btn last"><span><span><img src="{$resources}/img/famfam/cancel.png" /> {$lang.cerrar}</span></span></button>
	{/if}
	</div>
	{if isset($smarty.request.step) && $smarty.request.step > 1}
		<button class="btn previous"><span><span><img src="{$resources}/img/famfam/arrow_left.png" /> {$lang.atras}</span></span></button>
	{/if}
	{if (!isset($smarty.request.step) || ( isset($smarty.request.step) && $smarty.request.step < 6 )) && $smarty.request.step != -1}
		<button class="btn next"><span><span>{$lang.siguiente} <img src="{$resources}/img/famfam/arrow_right.png" /></span></span></button>
	{/if}
	{if $smarty.request.step == -1}		
		<button class="btn closePermanently" style="float:left">
			<span><span><img src="{$resources}/img/famfam/delete.png" /> {$lang.ocultar_asistente}</span></span>
		</button>
		<button class="btn close" style="float:right">
			<span><span><img src="{$resources}/img/famfam/cancel.png" /> {$lang.cerrar_asistente}</span></span>
		</button>
	{/if}
	
</div>
