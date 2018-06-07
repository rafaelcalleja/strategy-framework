
Hola,<br><br>

  Ha habido un pago que ha llegado con el estado: {$status}. <br><br>

  Este pago pertenece a una factura de

  {if $element instanceof invoice}
  	validación.<br><br>
 {else}
 	licencia.<br><br>
 {/if}
  
  La empresa que lo ha relizado es <strong>{$company->getUserVisibleName()}</strong> con id <strong>{$company->getUID()}</strong>.<br>
  {if $txnId} El txn_id de la transacción es: <strong>{$txnId}</strong>. {/if}<br>
  Por favor, tome las acciones necesarias...