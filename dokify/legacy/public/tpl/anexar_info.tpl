<div class="box-title">{$succes}</div>

	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}

	{assign var=empresa value=$user->getCompany()}

	<div class="cbox-content" style="width: 600px" id="reloader">
		<div class="padded center">
			{if $expiredDate}
				{$lang.document_about_expire} <span class="text-big strong">{'d-m-Y'|date:$expiredDate}</span>
			{else}
				{$lang.document_not_expire}
			{/if}
		</div>
		<hr />
		<div class="padded center">
			{if $partners}
				{assign var=validationMinTime value='validation::MIN_TIME_VALIDATE'|constant}
				{assign var=validationMaxTime value='validation::MAX_TIME_VALIDATE_NORMAL'|constant}

				{if isset($AVGValidation) && $AVGValidation > $validationMinTime && $AVGValidation < $validationMaxTime }				
					{assign var=AVGTime value="util::secsToHuman"|call_user_func:$AVGValidation}
					{$lang.average_time_validation|sprintf:$AVGTime:"48"}
				{else}
					{$lang.about_to_validate_normal}
				{/if}

				{if isset($isUrgent) && $isUrgent}
					<span class='text-big strong'><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/lightning_rojo.png" style="margin-right :6px; vertical-align: middle;"/>{$lang.validacion_urgente}</span></span>
					<br><br>
				{else}
					<button class='btn' href='applyUrgentValidation.php?fileId={$fileId}&poid={$elementId}&m={$moduleName}'><span class='text-big strong'><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/lightning_rojo.png" style="margin-right :6px; vertical-align: middle;"/>{$lang.request_urgent_validation}</span></span></button>
					<br><br>
				{/if}

			{else}
				{$waitingMessage}
			{/if}
		</div>
		{assign var=context value='tip::CONTEXT_ATTACH'|constant}
		{assign var=tip value="tip::getRandomTip"|call_user_func:$context}
		{if $tip}
			{$tip->getHTML()}
		{/if}
	</div>

	{foreach from=$selected item=element}
		<input type="hidden" name="selected[]" value="{$element->getUID()}" />
	{/foreach}

<div class="cboxButtons"></div>