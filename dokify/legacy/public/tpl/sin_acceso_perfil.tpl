<div class="box-pre-title bad-profile" style="padding: 0 20px;">
	{if $user->esStaff()}
		Tu perfil no tiene acceso!!!


		{if isset($objeto) && $perfil = $user->buscarPerfilAcceso($objeto) }
			&nbsp;&nbsp; <a class="changeprofile" to="{$perfil->getUID()}" >Cambiar a {$perfil->getUserVisibleName()}</a>
		{/if}
	{else}
		No tienes acceso a este elemento </div> {php}exit;{/php}
	{/if}
</div>
