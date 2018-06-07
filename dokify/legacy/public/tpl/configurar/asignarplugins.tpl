
<div class="box-title">
	{$lang.asignar_plugins}
</div>
<form name="asignar-plugins" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" id="asignar-plugins">
	<div class="cbox-content" style="text-align: center">
		{include file=$errorpath }
		{include file=$succespath }
		{include file=$infopath }
		<div class="message highlight" style="width: 600px;">
			
			<table class="asignar" style="width: 100%;">
					<thead>
						<tr>
							<th > <a class="light checkall" target="#plugins-disponibles">marcar/desmarcar</a> Disponibles </th> 
							<th style="width: 9%;"> </th>
							<th > <a class="light checkall" target="#plugins-asignados">marcar/desmarcar</a> Asignados </th>
						</tr>
					</thead>
					<tr>
						<td class="filed-list">
							<ul id="plugins-disponibles">

								{foreach from=$plugins_disponibles item=plugin}
									<li>
										<input type="hidden" name="plugins-disponibles[]" value="{$plugin.uid_plugin}" />
										
										<label for="lbl-{$plugin.nombre}"><input type="checkbox" class="line-assign" id="lbl-{$plugin.nombre}"/> <span class="ucase">{$plugin.nombre}</span></label>
									</li>
								{/foreach}

							</ul>
						</td>
						<td style="border: 0px; text-align: center;">
							<button class="btn list-move" style="margin-bottom: 2px;" rel="#plugins-asignados" target="#plugins-disponibles">
								<span><span> &nbsp; &laquo; &nbsp; </span></span>
							</button>
							<br />
							<button class="btn list-move" rel="#plugins-disponibles" target="#plugins-asignados" >
								<span><span> &nbsp; &raquo; &nbsp; </span></span>
							</button> 
						</td>
						<td class="filed-list">
							<ul id="plugins-asignados">
								{foreach from=$plugins_asignados item=plugin}
									<li>
										<input type="hidden" name="plugins-asignados[]" value="{$plugin.oid}" />
										<label for="lbl-{$plugin.nombre}"><input type="checkbox" class="line-assign" /> <span class="ucase">{$plugin.nombre}</span></label>
									</li>
								{/foreach}
							</ul>
						</td>
					</tr>
					<tr><td colspan="3" style="border: 0px"></td></tr>
			</table>
			<input type="hidden" name="send" value="1" />
			<input type="hidden" name="poid" value="{$smarty.get.poid}" />
		</div>
	</div>
	<div class="cboxButtons">
			<button class="btn" type="submit" onclick='this.disabled="true"'><span><span> {$lang.asignar} </span></span></button> 
	</div>
</form>
