{if $wrap}<table>{/if}
<tbody><tr><td colspan="3">{$elemento->getUserVisibleName()} ({$elemento->obtenerDato('dni')})</td></tr>
{if $headers}
<tr><td>EPI</td><td>PROXIMA REVISION</td><td>FIN VIDA UTIL</td></tr>
{/if}
{$resumen}
</tbody>
{if $wrap}</table>{/if}