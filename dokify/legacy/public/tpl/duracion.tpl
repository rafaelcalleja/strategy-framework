<div style="text-align: center">
	<div class="cbox-content">
		<div style="margin-bottom:1em">
			Fecha de comienzo de trabajo &nbsp; <input type="text" class="datepicker" name="startdate" size="10" value="{$startdate|default:'d/m/Y'|date}" />
		</div>
		<hr />
		<div>
			{$lang.duracion_agrupador_asignacion}
			<div style="line-height: 2em;" target="" class="slider line-block" count="{$duracioncount}" transform="" value="{$duracionvalue}"></div><input type="text" name="duracion" class="slider-value" value="{$duracionvalue}" id="duracion-value" />
		</div>
	</div>
</div>
