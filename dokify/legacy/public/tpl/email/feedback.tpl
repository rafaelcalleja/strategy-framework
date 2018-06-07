<div style="margin: 0px; height: 100%; widht: 100%; border:1px solid #FAD42E;">
	<div style="background-color:#FBEC88; border-bottom:1px solid #FAD42E;font-size:17px;margin-bottom:10px;line-height:2em;text-indent:10px;">
		Nueva sugerencia enviada
	</div>
	<div style="font-size:100%;">
		<div>
			<div style="border-bottom: 1px solid #ccc; padding: 10px;">
				Se ha recibido una nueva sugerencia/incidencia por parte de un usuario de AGD <br />
			</div>

			<div style="border-bottom: 1px solid #ccc; padding: 10px;">
				<table>
					<tr>
						<td> <strong>Tipo de incidencia</strong> </td>
						<td style="width: 10px"></td>
						<td> {$tipo} </td>
					</tr>
					<tr>
						<td> <strong>Usuario</strong> </td>
						<td style="width: 10px"></td>
						<td> {$usuario->getUserVisibleName()} - <a href="http://agd.afianza.net/agd/#buscar.php?p=0&q=tipo:usuario%20uid:{$usuario->getUID()}">Mostrar en la aplicacion</a> </td>
					</tr>
					<tr>
						<td> <strong>Cliente</strong> </td>
						<td style="width: 10px"></td>
						<td> {$cliente->getUserVisibleName()} </td>
					</tr>
					<tr>
						<td> <strong>Empresa</strong> </td>
						<td style="width: 10px"></td>
						<td> {$empresa->getUserVisibleName()} - <a href="http://agd.afianza.net/agd/#buscar.php?p=0&q=tipo:empresa%20uid:{$empresa->getUID()}">Mostrar en la aplicacion</a> </td>
					</tr>
				</table>
			</div>
			
			<div style="padding: 10px;">
				<strong>Sugerencia:</strong> <br />
				<div style="margin-left: 10px">
					{$texto}
				</div>
			</div>
		</div>
	</div>
</div>
