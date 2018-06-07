
Hola,<br><br>

  Ha habido un pago que no se ha realizado con la cantidad adecuada. <br><br>

  {if $invoice}
  	Tipo: Factura de validación.<br><br>
  {else}
  	Tipo: Pago de licencia.<br><br>
  {/if}

  Se esperaba la cantidad de <b>{$expectedAmount}€</b> y se ha recibido <b>{$payedAmount}€</b>.<br><br>

  La empresa que lo ha relizado es <b>{$company->getUserVisibleName()}</b> con id <b>{$company->getUID()}</b>.<br><br>