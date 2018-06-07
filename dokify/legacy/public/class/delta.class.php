<?php
	
	class delta {

		const SIN_BAJA = 0;
		const CON_BAJA = 1;
		
		protected $type;
		protected $collection;
		protected $xml;

		/*
		 *	Valida $collection es muy importante para que todos los accidentes recibidos tengan las siguientes caracteristicas:
		 *		- Dentro del mismo mes
		 *		- Dentro del mismo centro de cotización
		 *		- Dentro del mismo tipo (baja / sin baja)
		 *
		 *
		 */
		public function __construct(ArrayObjectList $collection, $type) {
			$tpl = Plantilla::singleton();
			$types = accidente::checkTypes($collection);
			if( count($types) == 0 ) {
				throw new Exception("No se han encontrado los accidentes que quieres exportar");
			} elseif ( count($types) > 1 ) {
				throw new Exception("No se pueden exportar accidentes de diferentes tipos (baja / sin baja)");
			}

			$dates = accidente::checkTypes($collection, "DATE_FORMAT(fecha_accidente, '%Y/%m')");
			if ( count($dates) == 0 ) {
				throw new Exception("No se han encontrado los accidentes que quieres exportar");
			} elseif ( count($dates) > 1 ) {
				throw new Exception("No se pueden exportar accidentes de meses diferentes");
			}

			$centros = accidente::checkTypes($collection, "uid_centrocotizacion");
			if ( count($centros) == 0 ) {
				throw new Exception("No se han encontrado los accidentes que quieres exportar");
			} elseif ( count($centros) > 1 ) {
				throw new Exception("No se pueden exportar accidentes de diferentes centros de cotización");
			} elseif ( count($centros) == 1 && ($uidcentro = reset($centros)) == 0 ){
				throw new Exception("Se debe especificar el centro de cotización al que pertenecen todos los empleados");
			}

			$this->collection = $collection;
			$this->type = $type;

			// Variables auxiliares
			$first = reset($this->collection);
			$centro = new centrocotizacion($uidcentro);
			$empleado = $first->obtenerEmpleado();
			$empresa = $first->obtenerEmpleado()->obtenerEmpresaContexto();

			// Strings de error / ayuda
			$datoNoEncontrado = $tpl->getString("dato_no_encontrado_ir");	
			$stringAqui = $tpl->getString("aqui");
			$this->cadenaError = self::check($first);
			if (count($this->cadenaError) ) {
				throw new Exception(implode('<br />',$this->cadenaError));
				die;
			} else {
							// Diferenciar cada tipo de parte
			switch ($this->type) {
				case self::SIN_BAJA: {
					$this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-1"?><!DOCTYPE MultiRATSB PUBLIC "-//Delta//RATSB//ES" "http://www.delta.mtas.es/Delta2Web/dtd/RATSB.dtd"><MultiRATSB><RATSB /></MultiRATSB>');
					$cabecera = $this->xml->RATSB->addChild('cabecera');
					$cabecera->addChild("numreferencia", "000000000000");
					$cabecera->addChild("mes", "000000000000"); // Mes
					$cabecera->addChild("anno", "000000000000"); // Año de los accidentes
					$cabecera->addChild("egc", "000000000000"); // Aseguradora
					$xmlempresa = $cabecera->addChild("empresa");
						$xmlempresa->addChild("razon", string_truncate($empresa->getUserVisibleName(),200,''));
						$xmlempresa->addChild("cif", $empresa->obtenerDato("cif"));
					$xmlcentro = $cabecera->addChild("centro");
						$xmlcentro->addChild("ccc", $centro->obtenerDato("codigo"));
						$xmlcentro->addChild("naf");
						$xmlcentro->addChild("provincia", $empleado->obtenerProvincia()->obtenerDato("codigo") );
						$xmlcentro->addChild("municipio", $empleado->obtenerMunicipio()->obtenerDato("codigo") );
					$xmlcnae = $xmlcentro->addChild("cnae");
						$xmlcnae->addChild("descripcion",string_truncate($centro->obtenerDato("texto_actividad_empresarial"),200,''));
						$xmlcnae->addChild("codigo", $centro->obtenerDato("codigo_actividad_empresarial"));
					// --------- FIN DE LA CABECERA, DATOS DE CADA ACCIDENTE
					foreach($this->collection as $accidente){
						$empleado = $accidente->obtenerEmpleado();
						$contrato = $empleado->obtenerTipoContrato();
						if( !$contrato ) throw new Exception( sprintf($datoNoEncontrado, "<strong>tipo de contrato</strong>", $empleado->obtenerURLFicha($stringAqui)) );
						//if( !$contacto = $accidente->obtenerDato("codigo_forma_lesion") ) throw new Exception( "El dato 'forma de lesión' debe estar almacenado");
						if ( !$contacto = $accidente->obtenerDato("codigo_forma_lesion") ) 
							throw new Exception( sprintf($datoNoEncontrado, "<strong>forma de lesión</strong>", $accidente->obtenerURLFicha($stringAqui)) );
						if ( !$partelesion = $accidente->obtenerDato("parte_cuerpo_lesion") ) 
							throw new Exception( sprintf($datoNoEncontrado, "<strong>parte del cuerpo lesionada</strong>", $accidente->obtenerURLFicha($stringAqui)) );
						if ( !$tipolesion = $accidente->obtenerDato("codigo_desviacion") ) 
							throw new Exception( sprintf($datoNoEncontrado, "<strong>código de desviación</strong>", $accidente->obtenerURLFicha($stringAqui)) );
						// Aunque la etiqueta de delta es accidentado, por los datos que conlleva, reprensenta un accidente
						$accidentado = $this->xml->RATSB->addChild('accidentado');
							$accidentado->addChild("nombreapelli", $empleado->obtenerDato("nombre") . " " . $empleado->obtenerDato("apellidos") );
							$accidentado->addChild("sexo", ($empleado->obtenerDato("sexo")=="Masculino")?"H":"M" );
							$accidentado->addChild("naf", "");
							$accidentado->addChild("ipf", $empleado->obtenerDato("dni"));
							$accidentado->addChild("contrato", $contrato->obtenerDato("codigo") );
							$accidentado->addChild("fechaacciden", date("dmY", $accidente->getTimestamp()) );
							$accidentado->addChild("contacto", $contacto );
							$accidentado->addChild("partelesion", $partelesion );
							$accidentado->addChild("tipolesion",  $tipolesion);
					}
					$xmlpie = $this->xml->RATSB->addChild('pie');
						$xmlpie->addChild("nombreapellidos", $accidente->obtenerDato("nombre_firmante_parte") );
						$xmlpie->addChild("calidadde", $accidente->obtenerDato("cargo_firmante_parte") );
						$xmlpie->addChild("provinc", "Madrid"); // Deberíamos poner un nuevo campo en el parte, pero este dato no es muy relevante
						$xmlpie->addChild("fechaemision", "");
						$xmlpie->addChild("fechaaceptacion", "");
						$xmlpie->addChild("codigoautoridad", "");
						$xmlpie->addChild("fecharecepcion", "");
						$xmlpie->addChild("rechazo", "");
				} break;
				case self::CON_BAJA: {
					$this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-1"?><!DOCTYPE MultiPAT PUBLIC "-//Delta//PAT//ES" "http://www.delta.mtas.es/Delta2Web/dtd/PAT.dtd"><MultiPAT/>');
					foreach ($this->collection as $accidente) {
						if (!empty($this->cadenaError)) {
							throw new Exception(implode('<br />',$cadenaError));
						}
						$empleado = $accidente->obtenerEmpleado();
						$empresa = $empleado->obtenerEmpresaContexto();
						$centrocotizacion = $accidente->obtenerCentrocotizacion();

						$PAT = $this->xml->addChild('PAT');
						// <!ELEMENT PAT (numreferencia, tipo, trabajador, empresa, ccclugar, accidente, asistenciales, economicos, actores) >
						$PAT->addChild('numreferencia','000000000000');
						$PAT->addChild('tipo','1');
						$xmlTrabajador = $PAT->addChild('trabajador');
						// <!ELEMENT trabajador (apellido1, apellido2, nombre, naf, fechaingreso, sexo, 
						// fechanacimiento, nacion, ipf, situacion, cno, antiguedad, contrato, regimenss,
						// textoconv, atep, domicilio, telefono, provincia, municipio, codpostal) >
						// <!ELEMENT cno (texto, codigo) >
						// <!ELEMENT antiguedad (meses, dias) >
						// <!ELEMENT atep (atepcnae, atepocupacion) >
						// $empleado = $accidente->obtenerEmpleado();
						$xmlTrabajador->addChild('apellido1',$empleado->obtenerDato('apellidos'));
						$xmlTrabajador->addChild('apellido2'); // no tenemos
						$xmlTrabajador->addChild('nombre',$empleado->obtenerDato('nombre'));
						$xmlTrabajador->addChild('naf',$empleado->obtenerDato('numero_seguridad_social'));
						$xmlTrabajador->addChild('fechaingreso',date("dmY", strtotime($empleado->obtenerDato('fecha_alta_empresa')))); // ddmmaaaa
						$xmlTrabajador->addChild('sexo',($empleado->obtenerDato('sexo')=='Femenino'?'M':'H'));
						$xmlTrabajador->addChild('fechanacimiento',date("dmY", strtotime($empleado->obtenerDato('fecha_nacimiento')))); // ddmmaaaa
						if ($codigoPais = $empleado->obtenerPais()->obtenerDato('codigo')) {
							$xmlTrabajador->addChild('nacion',$codigoPais);
						}
						$ipf = $empleado->obtenerDato('dni');
						$ipfPrefix = empleado::tipoDocumentoIdentificacion($ipf);
						$xmlTrabajador->addChild('ipf',$ipfPrefix.$ipf);
						$xmlTrabajador->addChild('situacion',$empleado->obtenerDato('situacion_profesional'));
						if ($uidCodigoOcupacion = $empleado->obtenerDato('uid_codigoocupacion')) {
							$cno = new codigoocupacion($uidCodigoOcupacion);
							$xmlCno = $xmlTrabajador->addChild('cno');
							$xmlCno->addChild('texto',$cno->obtenerDato('nombre'));
							$xmlCno->addChild('codigo',$cno->obtenerDato('codigo'));
						}
						if ($antiguedad = $empleado->obtenerAntiguedad(DateTime::createFromFormat('Y-m-d',$accidente->obtenerDato('fecha_accidente')))) {
							$xmlAntiguedad = $xmlTrabajador->addChild('antiguedad');
							$xmlAntiguedad->addChild('meses',$antiguedad->m);
							$xmlAntiguedad->addChild('dias',$antiguedad->d);
						}
						$xmlTrabajador->addChild('contrato',$empleado->obtenerDato('uid_tipocontrato'));
						$xmlTrabajador->addChild('regimenss',$empleado->obtenerDato('regimen_seguridad_social'));
						$xmlTrabajador->addChild('textoconv'); // texto del convenio: FormatoRemesasPAT.pdf:11. no tenemos.
						if ($uidCnae = $empleado->obtenerDato('uid_cnae')) {
							$cnae = new cnae($uidCnae);
							$xmlAtep = $xmlTrabajador->addChild('atep');
							$xmlAtep->addChild('atepcnae',$cnae->obtenerDato('codigo'));
							$xmlAtep->addChild('atepocupacion'); // no obligatorio
						}
						$xmlTrabajador->addChild('domicilio',$empleado->obtenerDato('direccion'));
						$xmlTrabajador->addChild('telefono',$empleado->obtenerDato('telefono'));
						if ($codigoProvincia = $empleado->obtenerProvincia()->obtenerDato('codigo')) {
							$xmlTrabajador->addChild('provincia',$codigoProvincia);
						}
						if ($codigoMunicipio = $empleado->obtenerMunicipio()->obtenerDato('codigo')) {
							$xmlTrabajador->addChild('municipio',$codigoMunicipio); 
						}
						$xmlTrabajador->addChild('codpostal',$empleado->obtenerDato('cp'));
						
						
						
						
						// $empresa = $empleado->obtenerEmpresaContexto();
						$xmlEmpresa = $PAT->addChild('empresa');
						// <!ELEMENT empresa (cifnif, razon, ccc, cnae, plantilla, domicilio, provincia, municipio, codpostal, telefono, contrata, ett, preventiva) >
						// <!ELEMENT cnae (texto, codigo) >
						// <!ELEMENT preventiva (asunpersona, servprevpro, servprevaje, trabdesigna, servprevman, ninguna) >
						$xmlEmpresa->addChild('cifnif',$empresa->obtenerDato('cif'));
						$xmlEmpresa->addChild('razon',string_truncate($empresa->obtenerDato('nombre'),200,''));
						if (!in_array($empleado->obtenerDato('situacion_profesional'),array('3','4'))) { 
							// codigo cuenta cotizacion de la empresa. obligatorio si (situacion!=3 && situacion!=4). FormatoRemesasPAT.pdf:16
							$xmlEmpresa->addChild('ccc',str_pad($empleado->obtenerCentrocotizacion()->obtenerDato('codigo'),11,'0',STR_PAD_LEFT)); 
						} else {
							$xmlEmpresa->addChild('ccc');
						}
						$xmlCnae = $xmlEmpresa->addChild('cnae'); // no tenemos estos datos para la empresa. los extraigo del centrocotizacion relevante
							$centroCotizacionCnae = $accidente->obtenerCentroCotizacion();
							$xmlCnae->addChild('texto',string_truncate($centroCotizacionCnae->obtenerDato('texto_actividad_empresarial'),200,''));
							$xmlCnae->addChild('codigo',$centroCotizacionCnae->obtenerDato('codigo_actividad_empresarial'));
						$xmlEmpresa->addChild('plantilla',$empresa->obtenerEmpleados(false,false,false,true)); // no tenemos este dato. cuento el numero de empleados.
						$xmlEmpresa->addChild('domicilio',$empresa->obtenerDato('direccion'));
						$xmlEmpresa->addChild('provincia',$empresa->obtenerDato('provincia')); // no está normalizado
						$xmlEmpresa->addChild('municipio',$empresa->obtenerDato('localidad')); // no está normalizado
						$xmlEmpresa->addChild('codpostal',$empresa->obtenerDato('cp'));
						$xmlEmpresa->addChild('telefono',$empresa->obtenerContactoPrincipal()->obtenerDato('telefono')); // no tenemos. pongo este por que es obligatorio
						$xmlEmpresa->addChild('contrata',$accidente->obtenerDato('como_subcontrata')?1:0);
						$xmlEmpresa->addChild('ett',$accidente->obtenerDato('como_ett')?1:0);
						$xmlPreventiva = $xmlEmpresa->addChild('preventiva');
							$xmlPreventiva->addChild('asunpersona');
							$xmlPreventiva->addChild('servprevpro');
							$xmlPreventiva->addChild('trabdesigna');
							$xmlPreventiva->addChild('servprevman');
							$xmlPreventiva->addChild('ninguna','1'); // al menos uno de estos campos debe ir cumplimentado. 
							
						$centrocotizacion = $accidente->obtenerCentroCotizacion();
						$xmlCcclugar = $PAT->addChild('ccclugar');
						//<!ELEMENT ccclugar (lugar, centro, datos) >
							$xmlLugar = $xmlCcclugar->addChild('lugar');
							//<!ELEMENT lugar (codigo, trafico, pais, provincia, municipio, direccion, viakm, otro) >
								$xmlLugar->addChild('codigo',$accidente->obtenerDato('lugar'));
								$xmlLugar->addChild('trafico',$accidente->obtenerDato('accidente_trafico')?1:0);
								if ($accidente->obtenerDato('accidente_trafico')==1 || in_array($accidente->obtenerDato('lugar'),array(accidente::PLACE_DESPLAZAMIENTO,accidente::PLACE_INITINERE))) {
									if ($paisAccidente = $accidente->obtenerPais()) {
										if ($codigoPaisAccidente = $paisAccidente->obtenerDato('codigo')) {
											$xmlLugar->addChild('pais',$codigoPaisAccidente);
										}
									}
									$xmlLugar->addChild('provincia',$accidente->obtenerProvincia()->obtenerDato('codigo'));
									$xmlLugar->addchild('municipio',$accidente->obtenerMunicipio()->obtenerDato('codigo'));
									$xmlLugar->addChild('direccion',$accidente->obtenerDato('direccion'));
									$xmlLugar->addChild('viakm',$accidente->obtenerDato('via_km'));
									$xmlLugar->addChild('otro',$accidente->obtenerDato('comentarios'));
								} else {
									$xmlLugar->addChild('pais');
									$xmlLugar->addChild('provincia');
									$xmlLugar->addchild('municipio');
									$xmlLugar->addChild('direccion');
									$xmlLugar->addChild('viakm');
									$xmlLugar->addChild('otro');
								}

							$xmlCentro = $xmlCcclugar->addChild('centro'); // en este elemento se indica si el accidente es en un CenCot de la empresa. por defecto pongo que no  por que no archivamos esos datos
							// <!ELEMENT centro (empresaep2, centroep2, tipoempresa, ciftipo) >
								if ($accidente->obtenerDato('lugar')==accidente::PLACE_OTROCENTRO) {
									$xmlCentro->addChild('empresaep2','0'); //ccc externo
									$xmlCentro->addChild('centroep2');
									$xmlCentro->addChild('tipoempresa');//OBLIGATORIO //  1: contrata, 2: ett, 3: otro tipo
									$xmlCentro->addChild('ciftipo');
								} else {
									$xmlCentro->addChild('empresaep2','1'); //ccc externo
									$xmlCentro->addChild('centroep2','0');
									$xmlCentro->addChild('tipoempresa');
									$xmlCentro->addChild('ciftipo');
								}

							$xmlDatos = $xmlCcclugar->addChild('datos');
							// <!ELEMENT datos (razon, domicilio, provincia, municipio, codpostal, telefono, plantilla, ccc, cnae) >
								$xmlDatos->addChild('razon',string_truncate($centrocotizacion->obtenerDato('nombre'),200,''));
								$xmlDatos->addChild('domicilio',$centrocotizacion->obtenerDato('domicilio'));
								$xmlDatos->addChild('provincia',$empresa->obtenerProvincia()->obtenerDato('codigo'));
								$xmlDatos->addChild('municipio',$empresa->obtenerMunicipio()->obtenerDato('codigo'));
								$xmlDatos->addChild('codpostal',$empresa->obtenerDato('cp'));
								$xmlDatos->addChild('telefono',$empresa->obtenerContactoPrincipal()->obtenerDato('telefono'));
								$xmlDatos->addChild('plantilla',count($centrocotizacion->obtenerEmpleados()));
								$xmlDatos->addChild('ccc'); // no tenemos
								$xmlDatosCnae = $xmlDatos->addChild('cnae');
									$xmlDatosCnae->addChild('texto',string_truncate($centrocotizacion->obtenerDato('texto_actividad_empresarial'),200,''));
									$xmlDatosCnae->addChild('codigo',$centrocotizacion->obtenerDato('codigo_actividad_empresarial'));
						$xmlAccidente = $PAT->addChild('accidente');
						// <!ELEMENT accidente (fechaaccidente, fechabaja, diasemana, hora, horatrabajo, habitual, evaluacion, descripcion, ampliacion) >
							$xmlAccidente->addChild('fechaaccidente',date("dmY", strtotime($accidente->obtenerDato('fecha_accidente'))));
							$xmlAccidente->addChild('fechabaja',date("dmY", strtotime($accidente->obtenerDato('fecha_baja'))));
							$xmlAccidente->addChild('diasemana',$accidente->obtenerDato('dia_semana'));
							$xmlAccidente->addChild('hora',$accidente->obtenerDato('hora_accidente'));
							$xmlAccidente->addChild('horatrabajo',$accidente->obtenerDato('hora_jornada'));
							if ($accidente->obtenerDato('lugar')!=accidente::PLACE_INITINERE) {
								$xmlAccidente->addChild('habitual',$accidente->obtenerDato('trabajo_habitual')?1:0);
							} else {
								$xmlAccidente->addChild('habitual');
							}
							$xmlAccidente->addChild('evaluacion',$accidente->obtenerDato('evaluacion_riesgos')?1:0);
							$xmlAccidente->addChild('descripcion',$accidente->obtenerDato('descripcion'));
							$xmlAmpliacion = $xmlAccidente->addChild('ampliacion');
							// <!ELEMENT ampliacion (entorno, proceso, tarea, desencadenante, modo, multiples, testigos, datostes) >
							// <!ELEMENT entorno (lug, tipolugar) >
							// <!ELEMENT proceso (trabajo, tipotrabajo) >
							// <!ELEMENT tarea (actividad, especifica, agente) >
							// <!ELEMENT desencadenante (hech, desv, agen) >
							// <!ELEMENT modo (tipomodo, formalesion, textoagente, agente) >
								$xmlEntorno = $xmlAmpliacion->addChild('entorno');
									$xmlEntorno->addChild('lug'/*,string_truncate($centrocotizacion->obtenerDato('nombre'),200,'')*/); // obligatorio, no tenemos
									$xmlEntorno->addChild('tipolugar'); // obligatorio, no tenemos
								$xmlProceso = $xmlAmpliacion->addChild('proceso');
									$xmlProceso->addChild('trabajo',string_truncate($accidente->obtenerDato('tipo_trabajo'),200,''));
									$xmlProceso->addChild('tipotrabajo',$accidente->obtenerDato('codigo_tipo_trabajo'));
								$xmlTarea = $xmlAmpliacion->addChild('tarea');
									$xmlTarea->addChild('actividad',string_truncate($accidente->obtenerDato('actividad_fisica'),200,''));
									$xmlTarea->addChild('especifica',$accidente->obtenerDato('codigo_actividad_fisica'));
									$xmlTarea->addChild('agente',$accidente->obtenerDato('codigo_agente_material_actividad'));
								$xmlDesencadenante = $xmlAmpliacion->addChild('desencadenante');
									$xmlDesencadenante->addChild('hech',string_truncate($accidente->obtenerDato('desviacion'),200,''));
									$xmlDesencadenante->addChild('desv',$accidente->obtenerDato('codigo_desviacion'));
									$xmlDesencadenante->addChild('agen',$accidente->obtenerDato('codigo_agente_material_desviacion'));
								$xmlModo = $xmlAmpliacion->addChild('modo');
									$xmlModo->addChild('tipomodo',string_truncate($accidente->obtenerDato('forma_lesion'),200,''));
									$xmlModo->addChild('formalesion',$accidente->obtenerDato('codigo_forma_lesion'));
									$xmlModo->addChild('textoagente',string_truncate($accidente->obtenerDato('agente_material_lesion'),200,''));
									$xmlModo->addChild('agente',$accidente->obtenerDato('codigo_agente_material_lesion'));
								$xmlAmpliacion->addChild('multiples',$accidente->obtenerDato('afecta_varios')?1:0);
								$xmlAmpliacion->addChild('testigos',(int)!!$accidente->obtenerDato('testigos'));
								$xmlAmpliacion->addChild('datostes',string_truncate($accidente->obtenerDato('testigos'),200,''));
						$xmlAsistenciales = $PAT->addChild('asistenciales');
						//<!ELEMENT asistenciales (lesion, grado, parte, medico, tipoasistenc, hospital) >
						// <!ELEMENT medico (nombre, domicilio, telefono) >
						// <!ELEMENT hospital (codigo, nombre) >
							$xmlAsistenciales->addChild('lesion',$accidente->obtenerDato('descripcion_lesion'));
							$xmlAsistenciales->addChild('grado',$accidente->obtenerGradoLesion()->obtenerDato('codigo'));
							$xmlAsistenciales->addChild('parte',$accidente->obtenerParteLesionada()->obtenerDato('codigo'));
							$xmlMedico = $xmlAsistenciales->addChild('medico');
								$xmlMedico->addChild('nombre',string_truncate($accidente->obtenerDato('medico_asistencia'),40,''));
								$xmlMedico->addChild('domicilio');
								$xmlMedico->addChild('telefono');
							$xmlAsistenciales->addChild('tipoasistenc',$accidente->obtenerTipoAsistencia()->obtenerDato('codigo'));
							$xmlHospital = $xmlAsistenciales->addChild('hospital');
								$xmlHospital->addChild('codigo',(int)!!$accidente->obtenerDato('lugar_hospitalizacion'));
								$xmlHospital->addChild('nombre',$accidente->obtenerDato('lugar_hospitalizacion'));
						$xmlEconomicos = $PAT->addChild('economicos');
						// <!ELEMENT economicos (mensual, anual, subsidio) >
						// <!ELEMENT mensual (mesanterior, dias, base) >
						// <!ELEMENT anual (b1, b2, total, promedio) >
						// <!ELEMENT subsidio (promedioa, promediob, total, indemnizac) >
							$xmlMensual = $xmlEconomicos->addChild('mensual');
								$xmlMensual->addChild('mesanterior',$accidente->obtenerDato('base_cotizacion_ultimo_mes'));
								$xmlMensual->addChild('dias',$accidente->obtenerDato('dias_cotizados_ultimo_mes'));
								$xmlMensual->addChild('base',$accidente->obtenerDato('base_reguladora_a'));
							$xmlAnual = $xmlEconomicos->addChild('anual');
								$xmlAnual->addChild('b1',$accidente->obtenerDato('base_cotizacion_b1'));
								$xmlAnual->addChild('b2',$accidente->obtenerDato('otros_conceptos_b2'));
								$xmlAnual->addChild('total',$accidente->obtenerDato('b1_b2'));
								$xmlAnual->addChild('promedio',$accidente->obtenerDato('promedio_diario_base_cotizacion'));
							$xmlSubsidio = $xmlEconomicos->addChild('subsidio');
								$xmlSubsidio->addChild('promedioa',$accidente->obtenerDato('promedio_diario_base_cotizacion'));
								$xmlSubsidio->addChild('promediob',$accidente->obtenerDato('base_reguladora_b'));
								$xmlSubsidio->addChild('total',$accidente->obtenerDato('total_base_reguladora_diaria'));
								$xmlSubsidio->addChild('indemnizac',$accidente->obtenerDato('cuantia_subsidio'));
						$xmlActores = $PAT->addChild('actores');
						// <!ELEMENT actores (fempresa, egc, alp, motivorechazo) >
						// <!ELEMENT fempresa (nombreapellid, calidadde, provincia, fechapresenta) >
						// <!ELEMENT egc (codigo, numexpediente, fechaaceptacion) >
						// <!ELEMENT alp (codigo, numexpediente, fecharecepcion) >

						
							$xmlFempresa = $xmlActores->addChild('fempresa');
								$xmlFempresa->addChild('nombreapellid',string_truncate($accidente->obtenerDato('nombre_firmante_parte'),100,''));
								$xmlFempresa->addChild('calidadde',string_truncate($accidente->obtenerDato('cargo_firmante_parte'),40,''));
								$provincia = $accidente->obtenerProvincia();
								if (isset($provincia) && $provincia) {
									$xmlFempresa->addChild('provincia',$provincia->obtenerDato('nombre'));
								} 								
								$xmlFempresa->addChild('fechapresenta',date('dmY'));
							$xmlEgc = $xmlActores->addChild('egc');
								$xmlEgc->addChild('codigo',$accidente->obtenerMutua()?$accidente->obtenerMutua()->obtenerDato('codigo'):'');
								$xmlEgc->addChild('numexpediente');
								$xmlEgc->addChild('fechaaceptacion');
							$xmlAlp = $xmlActores->addChild('alp');
								$xmlAlp->addChild('codigo');
								$xmlAlp->addChild('numexpediente');
								$xmlAlp->addChild('fecharecepcion');
							$xmlActores->addChild('motivorechazo');
					}
				} break;
			}
			}



			
		}
		public function asXML(){
			return $this->xml->asXML();
		}
		
		private static function check(elemento $accidente) {
			$tpl = Plantilla::singleton();
			$datoNoEncontrado = $tpl->getString("dato_no_encontrado_ir");	
			$stringAqui = $tpl->getString("aqui");
			$empleado = $accidente->obtenerEmpleado();
			$empresa = $empleado->obtenerEmpresaContexto();
			$centrocotizacion = $accidente->obtenerCentrocotizacion();
			$cadenaError = array();

			foreach (empleado::publicFields(elemento::PUBLIFIELDS_MODE_DELTA,$empleado) as $campo) {
				if ( !$empleado->obtenerDato($campo) ) {
					$cadenaError[] = sprintf($datoNoEncontrado, "<strong>".$tpl->getString($campo)."</strong>", $empleado->obtenerURLFicha($stringAqui));
				}
			}
			foreach (accidente::publicFields(elemento::PUBLIFIELDS_MODE_DELTA) as $campo){
				if ( !$accidente->obtenerDato($campo) ) {
					$cadenaError[] = sprintf($datoNoEncontrado, "<strong>".$tpl->getString($campo)."</strong>", $accidente->obtenerURLFicha($stringAqui));
				}	
			}
			foreach (empresa::publicFields(elemento::PUBLIFIELDS_MODE_DELTA) as $campo) {
				if ( !$empresa->obtenerDato($campo) ) {
					$cadenaError[] = sprintf($datoNoEncontrado, "<strong>".$tpl->getString($campo)."</strong>", $empresa->obtenerURLFicha($stringAqui));
				}	
			}
			foreach (centrocotizacion::publicFields(elemento::PUBLIFIELDS_MODE_DELTA) as $campo) {
				if ( !$centrocotizacion->obtenerDato($campo) ) {
					$cadenaError[] = sprintf($datoNoEncontrado, "<strong>".$tpl->getString($campo)."</strong>", $centrocotizacion->obtenerURLFicha($stringAqui));
				}	
			}
			// COMPROBANDO OTROS DATOS DEL BLOQUE TRABAJADOR
			if ( $empleado->obtenerDato('uid_provincia') != '99' && !$empleado->obtenerDato('uid_municipio') ) 
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>municipio</strong>", $empleado->obtenerURLFicha($stringAqui));
			if ( !in_array($empleado->obtenerDato('situacion_profesional'),array('3','4')) && !$empleado->obtenerDato('uid_tipocontrato') ) 
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>tipo de contrato</strong>", $empleado->obtenerURLFicha($stringAqui));
				
			// COMPROBANDO OTROS DATOS DEL BLOQUE EMPRESA
			if ( $contactoEmpresa = $empresa->obtenerContactoPrincipal()) {
				if ( !$telefonoEmpresa = $contactoEmpresa->obtenerDato('telefono') )
					$cadenaError[] = sprintf($datoNoEncontrado, "<strong>teléfono (del contacto principal)</strong>", $contactoEmpresa->obtenerURLFicha($stringAqui));
			} else {
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>contacto principal</strong>", $empresa->obtenerURLFicha($stringAqui));
			}
			
			
			if ( !($empleado->obtenerCentrocotizacion())) {
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>centro de cotización del empleado</strong>", $empleado->obtenerURLFicha($stringAqui));				
			} else if ( !in_array($empleado->obtenerDato('situacion_profesional'),array('3','4')) && !$centrocotizacion->obtenerDato('codigo') ) 
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>código cuenta cotización (del centro de cotización al que está asignado el empleado que ha sufrido el accidente)</strong>", $empleado->obtenerCentrocotizacion()->obtenerURLFicha($stringAqui));


				if (in_array($empleado->obtenerDato('situacion_profesional'),array(empleado::SITUACION_AUTONOMO_SIN_ASALARIADOS,empleado::SITUACION_AUTONOMO_CON_ASALARIADOS)) 
			&& $accidente->obtenerDato('lugar') == accidente::PLACE_INITINERE) {
				$cadenaError[] = "Los trabajadores autónomos no pueden tener accidentes in itinere ";
			}
			if ( $accidente->obtenerDato('trafico')==1 || in_array($accidente->obtenerDato('lugar'), array(accidente::PLACE_DESPLAZAMIENTO,accidente::PLACE_INITINERE))) {
				if (!$paisAccidente = $accidente->obtenerPais()) 
					$cadenaError[] = "Los accidentes de tráfico/desplazamiento/in itinere requieren datos adicionales: ".sprintf($datoNoEncontrado, "<strong>pais</strong>", $accidente->obtenerURLFicha($stringAqui)) ;
				if (!$provinciaAccidente = $accidente->obtenerProvincia()) 
					$cadenaError[] = "Los accidentes de tráfico/desplazamiento/in itinere requieren datos adicionales: ".sprintf($datoNoEncontrado, "<strong>provincia</strong>", $accidente->obtenerURLFicha($stringAqui));
				if (!$municipioAccidente = $accidente->obtenerMunicipio())
					$cadenaError[] = "Los accidentes de tráfico/desplazamiento/in itinere requieren datos adicionales: ".sprintf($datoNoEncontrado, "<strong>municipio</strong>", $accidente->obtenerURLFicha($stringAqui)) ;
				if (!$direccionAccidente = $accidente->obtenerDato('direccion') 
				&& !$viakmAccidente = $accidente->obtenerDato('via_km') 
				&& !$comentariosAccidente = $accidente->obtenerDato('comentarios'))
					$cadenaError[] =  "Los accidentes de tráfico/desplazamiento/in itinere requieren datos adicionales: ".sprintf($datoNoEncontrado, "<strong>direccion/via/km/otros</strong>", $accidente->obtenerURLFicha($stringAqui)) ;
			}

			// COMPROBANDO OTROS DATOS DEL BLOQUE ACCIDENTE
			if ($accidente->obtenerDato('lugar') != accidente::PLACE_INITINERE && in_array($accidente->obtenerDato('hora_jornada'),array(empleado::JORNADA_YENDO,empleado::JORNADA_VOLVIENDO)))
				$cadenaError[] = sprintf('Un accidente in itinere debe ocurrir yendo o volviendo del trabajo. Compruebe la %s.', "<strong>hora del accidente dentro de la jornada</strong>", $accidente->obtenerURLFicha($stringAqui)) ;

			// COMPROBANDO OTROS DATOS DEL BLOQUE ASISTENCIA
			if (!$descripcionLesionAccidente = $accidente->obtenerDato('descripcion_lesion')) {
				$cadenaError[] = sprintf($datoNoEncontrado, "<strong>descripción de la lesión</strong>", $accidente->obtenerURLFicha($stringAqui));
			} else if ($accidente->obtenerGradoLesion()->obtenerDato('codigo') == accidente::GRADO_FALLECIMIENTO 
					&& $accidente->obtenerLesion()->obtenerDato('codigo') == '82' ){
				// si indicamos que el accidente es mortal, el tipo de lesión no puede ser el siguiente.
				// 082 Ahogamiento y sumersiones no mortales
				$cadenaError[] = "El grado de lesión no puede ser {$accidente->obtenerGradoLesion()->obtenerDato('nombre')} con descripción {$accidente->obtenerLesion()->obtenerDato('nombre')}";
			}
			return $cadenaError;
		}
		public function download(){
			$xml = $this->xml->asXML();
			header("Content-Type: text/xml");
			header('Content-Disposition: attachment; filename="delta-'.date("d-m-Y") . '.xml"');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ". strlen($xml));
			die($xml);
		}
	}
?>
