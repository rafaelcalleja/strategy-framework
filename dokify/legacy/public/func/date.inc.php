<?php
	function get_month_name($num){
		$num = (int) $num;
		$names = array();
		$names[1] = "Enero";
		$names[] = "Febrero";
		$names[] = "Marzo";
		$names[] = "Abril";
		$names[] = "Mayo";
		$names[] = "Junio";
		$names[] = "Julio";
		$names[] = "Agosto";
		$names[] = "Septiembre";
		$names[] = "Octubre";
		$names[] = "Noviembre";
		$names[] = "Diciembre";

		$tpl = Plantilla::singleton();
		return $tpl->getString( strtolower($names[$num]) ); 
	}
?>
