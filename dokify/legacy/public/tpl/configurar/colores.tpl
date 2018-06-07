<style>
{$estilos}
</style>
<div class="box-title">
	{$lang.configurar_colores} 
</div>
<form method="POST" action="{$smarty.server.PHP_SELF}" class="form-to-box">
  	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content">
	  <p>Clic en (rueda) para cambiar el color de la etiqueta.<br />Clic en (contraste) para alternar el color del texto entre negro y blanco.</p>
{$campos}
	</div>
<div class="cboxButtons">
	<button class="btn"><span><span> {$lang.continuar} </span></span></button>
</div>	
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="cliente" value="{$cliente}" />
</form>