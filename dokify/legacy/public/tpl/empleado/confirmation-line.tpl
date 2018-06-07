{if $empleado->isManager()}
	{if $empleado->hasConfirmed()}
		{assign var=current value=$empleado->getConfirmationTime()}
		{assign var=next value=$empleado->nextConfirmationTime()}
		<div class="line-ok">
			Se ha registrado tu conformidad hasta {$next|date_format:"%d/%m/%Y"} 

			{if $current|date_format:"%d/%m/%Y" != $smarty.const.now|date_format:"%d/%m/%Y"}
				<button class="btn post" href="conformidad.php"><span><span> Confirmar de nuevo </span></span></button>
			{/if}
		</div>
	{else}
		<div class="line-error">
			Es necesario estar conforme con toda la información de tu grupo <button class="btn post" href="conformidad.php"><span><span> Dar Conformidad </span></span></button>
		</div>
	{/if}
{/if}
