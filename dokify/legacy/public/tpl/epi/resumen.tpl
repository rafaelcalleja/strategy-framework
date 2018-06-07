{assign var="caducidad" value=$elemento->obtenerFechaCaducidad()}
{assign var="fechacaducidad" value=$elemento->obtenerFechaCaducidad(true)}
{assign var="revision" value=$elemento->revisionProxima()}
{assign var="fecharevision" value=$elemento->revisionProxima(true)}
{if $headers}
	<tr><td>EPI</td><td>PROXIMA REVISION</td><td>FIN VIDA UTIL</td></tr>
{/if}	
<tr><td>{$elemento->getUserVisibleName()} ({$elemento->obtenerDato('nserie')})</td>
<td style="{if isset($revision)}color: red;{/if}">{if isset($revision)}{$fecharevision}{else}-{/if}</td>
<td style="{if $elemento->caducidadProxima()}color: red;{/if}">{if isset($caducidad)}{$fechacaducidad}{else}-{/if}</td></tr>
