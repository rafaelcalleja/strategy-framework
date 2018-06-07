
{if isset($smarty.get.step) && $smarty.get.step > 1 }
	{if $smarty.get.step == 2}
		<p>
			Bien. Es posible que aún no tengas ninguna máquina dada de alta en la herramienta. 
			<img class="box-it" href="maquina/nuevo.php" src="{$resources}/img/48x48/iface/boxadd.png" style="cursor:pointer; float: right; margin-left: 10px;" height="34px" />
			Para hacer este proceso localiza en la pantalla un icono como el que aparece a la derecha y haz click, luego completa el formulario para registrar una maquina
			<br /><br />
			Si ya has dado de alta tu maquinaria puedes continuar con el siguiente paso
		</p>
	{else if $smarty.get.step == 3}
		<p>
			Para poder utilizar la herramienta es necesario activar los elementos. Para comenzar con el proceso debemos localizar el botón 

			<button class="btn" href="/app/payment/license"><span><span>{$lang.realizar_pago} &nbsp;<img style="vertical-align:middle" src="{$resources}/img/common/paypal.png"></span></span></button>
		</p>
		<p>
			Una vez completado el proceso de pago podremos acceder a todas las opciones de nuestros elementos y empezar a cargar la documentación.
			Si necesita ayuda haga click <a href="ayuda.php" class="box-it">aquí</a>
		</p>
	{/if}
{else}
	<p>
		Bienvenido a AGD. En este asistente te explicamos brevemente los primeros pasos para comenzar a utilizar la herramienta.
	</p>
	<p>
		La parte mas básica es el menú principal, que consta de pestañas como <strong>Maquinaria</strong> o <strong>Empleados</strong>. 
		Vamos a pulsar en <a href="#maquina/listado.php"><strong>Maquinaria</strong></a> para continuar con el ejemplo.
	</p>
{/if}
