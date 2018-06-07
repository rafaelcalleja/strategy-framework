<div>
	{if $isOk}
		<div>
			<div style="background-color: #DFF2BF; border: 1px solid #4F8A10; padding: 10px; margin-bottom:20px;">
				<table border="0">
					<tr>
						<td><img style="vertical-align: middle;" src="{$smarty.const.RESOURCES_DOMAIN}/img/common/tick.png"></td>
						<td><span style="color: #4F8A10; font-weight: bold; font-size:20px;">{$lang.summary_ok_title}</span></td>
					</tr>
				</table>
			</div>
		</div>
		<div style="margin-top:20px">
			<span style="vertical-align: middle;">{$lang.summary_ok_message}</span>
		</div>
	{else}
		<div style="background-color: #FFBABA; border: 1px solid #D8000C; padding: 10px; margin-bottom:20px;">
			<table border="0">
				<tr>
					<td><img style="vertical-align: middle;" src="{$smarty.const.RESOURCES_DOMAIN}/img/common/cross.png"></td>
					<td><span style="color: #C7444B; font-weight: bold; font-size:20px;">{$lang.summary_error_title}</span></td>
				</tr>
			</table>
		</div>


		<div style="margin-top:20px">
			{$lang.summary_error_message}

			{if $lineasEmpresa}

				{if $lineasEmpresa>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}
				{assign var="empresa" value="$lang.empresa|lower}
				{$lang.tienes_que_revisar_documentos|sprintf:$urlEmpresa:$lineasEmpresa:$documentos:$empresa}
				<br>
			{/if}

			{if $lineasEmpresaCaducan}
				
				{if $lineasEmpresaCaducan>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}
				{assign var="empresa" value="$lang.empresa|lower}
				{$lang.tienes_documentos_caducados|sprintf:$urlEmpresaCaducan:$lineasEmpresaCaducan:$documentos:$empresa}
				<br>
			{/if}

			{if $lineasEmpleado}

				{if $lineasEmpleado>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}
				{assign var="empleado" value="$lang.empleado|lower}
				{$lang.tienes_que_revisar_documentos|sprintf:$urlEmpleado:$lineasEmpleado:$documentos:$empleado}
				<br>
			{/if}

			{if $lineasEmpleadoCaducan}

				{if $lineasEmpleadoCaducan>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}
				{assign var="empleado" value="$lang.empleado|lower}
				{$lang.tienes_documentos_caducados|sprintf:$urlEmpleadoCaducan:$lineasEmpleadoCaducan:$documentos:$empleado}
				<br>
			{/if}
			

			{if $lineasMaquina}

				{if $lineasMaquina>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}
				{assign var="maquina" value="$lang.maquina|lower}
				{$lang.tienes_que_revisar_documentos|sprintf:$urlMaquina:$lineasMaquina:$documentos:$maquina}
				<br>
			{/if}
			
			{if $lineasMaquinaCaducan}

				{if $lineasMaquinaCaducan>1}
					{assign var="documentos" value=$lang.documento|lower|cat:"s"}
				{else}
					{assign var="documentos" value="$lang.documento|lower}
				{/if}

				{assign var="maquina" value="$lang.maquina|lower}
				{$lang.tienes_documentos_caducados|sprintf:$urlMaquinaCaducan:$lineasMaquinaCaducan:$documentos:$maquina}
				<br>
			{/if}
		</div>
	{/if}
	<div  style="width:100%; margin-top:20px; height:30px; margin-bottom: 20px;">
		<div style="float:left; margin-top:11px">
			{$lang.email_pie_equipo}
		</div>
		<div style="float:right; margin-top: -5px;"><img src="{$elemento_logo}" height="50" alt="logo-dokify" /></div>
	</div>
	<div width="100%">
		{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
	</div>
	
</div>