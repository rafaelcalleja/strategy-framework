{*
	CREA FORMULARIOS SIMPLES CON INFORMACION ADICIONAL PARA LAS CLASE EXTENDED-CELL

	· $data -> array con los datos de filas y columnas que tenemos que pintar
	· $empresa -> es el elemento sobre el que vamos a mostrar informacion
	· $data -> puede haber tantos elementos como se necesite
			[ string || int ] =  array(
				array( "key" => "value", "key" => "value" ),
				array( "key" => "value", "key" => "value" ),
				array( "key" => "value", "key" => "value" )
			)
*}

	<div class="extended-cell-info" style="background-color:#fff;color:#000">
		<div>
			<table>
			{foreach from=$data key=i item=line}
					{if $i==0 && isset($titulos_columnas)}
						<thead>
							<tr>
								{foreach from=$line key=campo item=valor}
									<th>{$campo}</th>
								{/foreach}
							</tr>
						</thead>
					{/if}

					<tr>
						{foreach from=$line key=field item=valor}
							<td style="white-space:nowrap;">{$valor}</td>
						{/foreach}
					</tr>
			{/foreach}	
			</table>		

		</div>
	</div>
