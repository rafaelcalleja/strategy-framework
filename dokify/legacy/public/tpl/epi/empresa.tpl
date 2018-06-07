<table border="1">
	<thead>
		<tr>
			<th colspan="3"><strong>{$elemento->getUserVisibleName()}</strong></th></tr>
		<tr>
			<th>EPI</th><th>PROXIMA REVISION</th><th>FIN VIDA UTIL</th>
		</tr>
	</thead>
	{if $resumenEmpleados}
	<tr><td colspan="3"><strong>EPIs asignados</strong></td></tr>{$resumenEmpleados}
	{/if}
	{if $resumenAlmacen}
	<tr><td colspan="3"><strong>EPIs en almacen</strong></td></tr>{$resumenAlmacen}
	{/if}
</table>