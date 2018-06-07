<div class="box-title">
	{$lang.primeros_pasos}
</div>
<div class="cbox-content">
	{include file=$tpldir|cat:'asistente/pagos/steps_'|cat:$locale|cat:'.inc.tpl'}
</div>
<div class="cboxButtons">
	<div style="float:left">
		<button class="btn close"><span><span><img src="{$resources}/img/famfam/cancel.png" /> {$lang.cerrar}</span></span></button>
	</div>
	{if isset($smarty.request.step) && $smarty.request.step > 1}
		<button class="btn previous"><span><span><img src="{$resources}/img/famfam/arrow_left.png" /> {$lang.atras}</span></span></button>
	{/if}
	{if !isset($smarty.request.step) || ( isset($smarty.request.step) && $smarty.request.step < 3 )}
		<button class="btn next"><span><span>{$lang.siguiente} <img src="{$resources}/img/famfam/arrow_right.png" /></span></span></button>
	{/if}
</div>
