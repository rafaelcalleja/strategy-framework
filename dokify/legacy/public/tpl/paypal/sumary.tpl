
<div class="box-title">
	Información de pagos · {$empresa->getUserVisibleName()}
</div>
<form>
	<div style="width: 640px; margin: 0 10px;" class="message {if $empresa->needsPay()}error{else}succes{/if}">
		{assign var="service" value=$empresa->getPayInfo()}
		<div >
			<table>
				<tr>
					<td class="form-colum-description"> Licencia Requerida: </td> <td class="form-column-value"> {$service->concept} </td>
					<td class="form-colum-description"> Coste licencia: </td> <td class="form-column-value"> {$service->price}€ </td>
				</tr>
			</table>
		</div>
	</div>

	{assign var="transactions" value=$empresa->getTransactions()}
	{if count($transactions)}
		<hr />
		<h2 style="margin: 10px"> Transacciones </h2>
		<div>

		{foreach from=$transactions item=transaction}
			<div class="box-message-block">
				<table>
					<tr>
						<td class="form-colum-description"> Fecha </td> <td class="form-column-value"> {$transaction.date} </td> 
						<td class="form-colum-description"> Estado </td> <td class="form-column-value"> 
							{$transaction.payment_status|default:"Incomplete"} 
							{if $transaction.payment_type}
								({$transaction.payment_type}) 
							{/if}
						</td>
					</tr>

						<td class="form-colum-description"> Total </td> <td class="form-column-value"> {$transaction.mc_gross|default:"n/a "}€ </td> 
						<td class="form-colum-description"> Licencia </td> <td class="form-column-value"> {$transaction.item_name|default:"No definido"} </td>
					</tr>
					<tr>
						{new result="pagador" type="usuario" uid=$transaction.uid_usuario}
						{assign var="restante" value=$transaction.en_uso}
						<td class="form-colum-description"> Usuario </td> <td class="form-column-value"> {$pagador->obtenerUrlFicha($pagador->getUserVisibleName())} </td> 
						<td class="form-colum-description"> Días restantes </td> <td class="form-column-value"> {if $transaction.payment_status} {math equation="y-x" x=$transaction.en_uso y=$transaction.daysValidLicense+1} {/if}</td>
					</tr>
					<tr>
						<td>
							Custom: 
						</td>
						<td colspan="3">							
							{$transaction.custom}
						</td>
					</tr>
				</table>
			</div>
		{/foreach}
		</div>
	{/if}
</form>
<div class="cboxButtons">
	{if $user->esStaff() && $empresa->getSelectedLicense() !== empresa::LICENSE_FREE}				
		<button class="btn box-it" href="/paypal/info.php?poid={$empresa->getUID()}&pasarfree=1"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" /> {$lang.pasar_free} </span></span></button> 
	{/if}

	{if $user->esAdministrador() && ($empresa->getSelectedLicense() === empresa::LICENSE_FREE || $empresa->timeFreameToRenewLicense() || $empresa->hasExpiredLicense())}				
		<button class="btn box-it" href="/paypal/info.php?poid={$empresa->getUID()}&event=transfer"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/money.png" /> {$lang.realizar_pago} </span></span></button> 
	{/if}
</div>
