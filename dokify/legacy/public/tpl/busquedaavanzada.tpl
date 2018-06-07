<div class="box-title">
	{$lang.buscar}
</div>
	<div style="text-align: center; width: 740px;">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}


	<div class="cbox-content" style="text-align: left; margin-top:10px;">
		<div id="advanced-search-content">
			<form class="advanced-search-form">
					<table width="100%">
						<tr>
							<td width="150px">{$lang.texto_a_buscar}:</td>
							<td> <input type="text" name="string" style="width:500px" /> </td>
						</tr>
						<tr>
							<td width="150px">{$lang.tipo_elemento}:</td>
							<td> 
								<select id="tipo" style="width: 500px;">
									<option value="">{$lang.selecciona_si_procede}</option>
									<option value="empresa">{$lang.empresas}</option>
									<option value="empleado">{$lang.empleados}</option>
									<option value="maquina">{$lang.maquinas}</option>
									<option value="usuario">{$lang.usuarios}</option>
									<option value="epi">{$lang.epis}</option>
									<option>-----------------</option>
									<option value="anexo-empresa">{$lang.empresa_documento}</option>
									<option value="anexo-empleado">{$lang.empleado_documento}</option>
									<option value="anexo-maquina">{$lang.maquina_documento}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								{$lang.asignacion}: 
							</td>
							<td style="white-space: nowrap"> 
									<select id="agrupamiento-asignado" style="width: 180px;">
										<option>{$lang.selecciona_si_procede}</option>
										{if $agrupamientos}
											{foreach from=$agrupamientos item=list key=name}
												<optgroup label="{$name}">
												{foreach from=$list item=agrupamiento key=i}
													<option value="{$agrupamiento->getUID()}">{$agrupamiento->getUserVisibleName()}</option>
												{/foreach}
												 </optgroup>
											{/foreach}
										{/if}
									</select>
									 &raquo; 
									<select name="asignado" id="asignado" style="width: 320px;">
										<option class="default"> &laquo;&laquo;&laquo; </option>
									</select>
							</td>
						</tr>
						<tr>
							<td> 
								{$lang.documentos}: 
							</td>
							<td> 
								<select name="docs" style="width: 180px;">
									<option>{$lang.selecciona_si_procede}</option>
									<option value="0">{$lang.sin_anexar}</option>
									<option value="1">{$lang.anexado}</option>
									<option value="2">{$lang.validado}</option>
									<option value="3">{$lang.caducado}</option>
									<option value="4">{$lang.anulado}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td> 
								{$lang.estado}: 
							</td>
							<td> 
								<select name="estado" style="width: 280px;">
									<option value="">{$lang.selecciona_si_procede}</option>
									<option value="ok">{$lang.todos_los_documento_estan_ok}</option>
									<option value="error">{$lang.algunos_documentos_no_validos}</option>
								</select>
							</td>
						</tr>
						<!--
						<tr>
							<td> 
								{$lang.elementos_en_papelera}: 
							</td>
							<td> 
								<select name="papelera" style="width: 280px;">
									<option selected>{$lang.mostrar_solo_activos}</option>
									<option value="all">{$lang.mostrar_todos}</option>
									<option value="1">{$lang.mostrar_solo_papelera}</option>
								</select>
							</td>
						</tr>
						-->
						<tr>
							<td colspan="2" style="text-align: center">
								<br />
								<div class="action-buttons">
									<button style="display:none"></button>
									<button class="btn" id="mas-and"><span><span>{$lang.anadir_datos_filtro}</span></span></button>
									<button class="btn" id="mas-or"><span><span>{$lang.sumar_resultados_busqueda}</span></span></button> 
								</div>
								<hr />
							</td>
						</tr>
					</table>
			
			</form>
		</div>
	</div>
	</div>

	<div style="text-align: center">
		<button class="green s" id="buscador-avanzado">{$lang.buscar}</button>
	</div>

	<div class="cboxButtons"></div>


