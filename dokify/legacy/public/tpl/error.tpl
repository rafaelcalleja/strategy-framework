<div class="box-title">
	{$lang.error}
</div>
<div class="cbox-content">
	<div class="message error">
		{if isset($lang.$message)}
			{$lang.$message}
		{else}
			{$message}
		{/if}
	</div>
</div>
<div class="cboxButtons">{include file=$tpldir|cat:'button-list.inc.tpl'}</div>
