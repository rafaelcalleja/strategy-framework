<div class="right-bar">
	<h1> Información del servidor </h1>
	<div class="padded">
		<table>
			{foreach from=$data item=val key=key}
				<tr>
					<td class="form-colum-description"> {$key}: </td> <td class="form-colum-value"> {$val} </td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>
