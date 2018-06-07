{assign var="empresa" value=$user->getCompany()}
{if isset($smarty.get.step) && $smarty.get.step > 0 }
	{if $smarty.get.step == 1}
		<p>
			Bienvenido a dokify. En este asistente te explicamos brevemente los primeros pasos para comenzar a utilizar la herramienta.
		</p>
		<p>
			En el menú superior, dividido en pestañas, encontrarás las opciones básicas.
		</p>
		<p>
			Empecemos con un ejemplo: haz clic en la pestaña <a href="#empleado/listado.php"><strong>Empleados</strong></a>.
		</p>
	{/if}

	{if $smarty.get.step == 2}
		<p>
			Da de alta un empleado utilizando el icono que aparece a la izquierda de la pantalla. 
			<br /><br />
			<img class="box-it" href="empleado/nuevo.php?poid={$empresa->getUID()}" src="{$resources}/img/48x48/iface/boxadd.png" style="cursor:pointer; float: left; margin-left: 10px;" height="34px" />
			<br /><br />
		</p>
		<p>
			Se te muestra la ventana <strong>Dar de alta un nuevo empleado.</strong> Rellena todos los campos y haz clic en el botón <strong>Añadir empleado</strong> para guardar los cambios.
		</p>
		<p>
			<a href="#support.php"><strong>Ver más</strong></a>.
		</p>
		<p>
			Cuando des de alta todos tus empleados continúa con el siguiente paso.
		</p>
	{/if}

	{if $smarty.get.step == 3}
		{assign var=mustPayCompanies value=$empresa->pagoPorSubcontratacion()}
		{assign var=noPay value=$empresa->obtenerDato('pago_no_obligatorio')}
		{assign var=isFree value=$empresa->isFree()}
		{if !$noPay && $isFree && $mustPayCompanies}
			<p>
				El cliente que te invita te pide el Certificado dokify, que obtendrás contratando el Plan Premium. Además del certificado, podrás disfrutar de numerosas ventajas adicionales: carga exprés, multiusuario, mayor capacidad de subida o portal del empleado, entre otras.
			</p>
			<p>
				Para contratar el Plan Premium haz clic aquí: <a class="btn" href="/app/payment/license"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" /> {$lang.contratar_plan_premium}</span></span></a> y completa el proceso de pago para que puedas acceder a todas las ventajas de dokify.				
			</p>
		{elseif $isFree}
			<p>
				Con tu plan Free puedes subir la documentación requerida por tus clientes pero debes hacerlo de uno en uno. Para subir la misma documentación a varios clientes a la vez, solicitar tu propia documentación y disfrutar de más ventajas contrata el plan Premium.
			</p>
			<p>
				¿Cómo? Haz clic aquí: <a class="btn" href="/app/payment/license"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" /> {$lang.contratar_plan_premium}</span></span></a> y completa el proceso de pago para que puedas acceder a todas las ventajas de dokify.
			</p>
			<p>
				Haz clic <a href="/app/payment/license">aquí</a> para saber más.	
			</p>			
		{else}
			<p>
				Ya tienes contratado el plan Premium. Ahora vamos a ver cómo enviar la documentación.
			</p>
		{/if}
	{/if}

	{if $smarty.get.step == 4}
		<div style="margin: 10px">
			Seguimos con el ejemplo. Ahora tienes que seleccionar qué tipo de trabajo realizan tus empleados. 
			<br /><br />

			Sin abandonar la pestaña <strong>Empleados</strong>, haz clic en el botón <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Opciones</li></ul></div> (en la parte derecha) en el empleado que desees configurar. Observa que se muestra un desplegable. Selecciona la opción <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Asignaciones</li></ul></div>.
		</div>
	{/if}

	{if $smarty.get.step == 5}
		<div style="margin: 8px">
			Mueve al apartado <strong>“Asignados”</strong> todos los elementos que deban estar asociados a cada empleado. Por ejemplo: sus puestos de trabajo, los proyectos en los que está trabajando, etc.
			<br /><br />
			Cuando finalices las asignaciones haz clic en el botón <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Guardar</li></ul></div>. A continuación, para ver los documentos solicitados al empleado, haz clic en <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Opciones</li></ul></div> > <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Documentos</li></ul></div>. Anexa los documentos que se te soliciten para finalizar.
			Pulsa el botón guardar cuando finalices. 
			<br /><br />
			<a href="#support.php"><strong>Ver más</strong></a>.
		</div>
	{/if}

	{if $smarty.get.step == 6}
		<p>
			Para ayudarte en cualquier proceso siempre dispones del botón  <a class="box-it btn" href="ayuda.php"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" /> {$lang.ayuda} </span></span></a>  situado en la parte superior de la pantalla. Desde ahí tendrás acceso al centro de ayuda, podrás descargarte un manual de uso o consultar tus dudas con el servicio de soporte técnico.
		</p>
	{/if}
{else}
	<div style="margin: 8px">
		<br /><br /><br /><br />
		<center>{$lang.mensaje_ocultar_asistente}</center> 									
	</div>
{/if}
