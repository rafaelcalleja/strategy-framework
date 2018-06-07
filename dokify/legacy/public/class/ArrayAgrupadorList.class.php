<?php

	class ArrayAgrupadorList extends ArrayObjectList {

		/*
		public function obtenerPorcentajeAsignacion(agrupador $agrupador){
			if( !$list = $this->obtenerIdElementosAsignados(8) ){ return false; }

			$db = db::singleton();

			$SQL = "SELECT count(uid_elemento) as c FROM ". TABLE_AGRUPADOR ."_elemento 
			WHERE uid_elemento IN ({$list->toComaList()}) AND uid_agrupador = {$agrupador->getUID()}
			GROUP BY uid_agrupador
			";


			$n = $db->query($SQL, 0, 0);
			return round( count($list) * $n / 100 );
		}*/


		public function getAsOptions () 
		{
			$SQL = "SELECT uid_agrupador, nombre FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupador IN ({$this->toComaList()})";

			$db = db::singleton();

			$data = $db->query($SQL, true);
			$viewData = array();
			foreach ($data as $value) {
				$viewData[$value["uid_agrupador"]] = utf8_encode($value["nombre"]);
			}

			return $viewData;
		}


		public function toOrganizationList () {
			$list = count($this) ? $this->toComaList() : "0";
			$SQL = "SELECT uid_agrupamiento FROM ". TABLE_AGRUPADOR . " WHERE uid_agrupador IN ({$list}) GROUP BY uid_agrupamiento";
			$collection = db::get($SQL, '*', 0, 'agrupamiento');

			if ($collection && count($collection)) return new ArrayAgrupamientoList($collection);
			return new ArrayAgrupamientoList;
		}


		public function obtenerAgrupadoresRelacionados (categorizable $item) {
			if (count($this)) {
				$SQL = "
					SELECT r.uid_agrupador 
					FROM ". TABLE_AGRUPADOR ."_elemento a INNER JOIN ". TABLE_AGRUPADOR ."_elemento_agrupador r USING(uid_agrupador_elemento)
					WHERE a.uid_agrupador IN ({$this->toComaList()}) AND uid_elemento = {$item->getUID()} AND uid_modulo = {$item->getModuleId()}
				";

				$collection = db::get($SQL, '*', 0, 'agrupador');
				if ($collection && count($collection)) return new self($collection);
			}

			return new self;
		}

		public function obtenerIdElementosAsignados($uidmodulo){
			$db = db::singleton();

			$SQL = "SELECT uid_elemento FROM ". TABLE_AGRUPADOR ."_elemento 
					WHERE uid_modulo = $uidmodulo AND uid_agrupador IN ({$this->toComaList()})
					GROUP BY uid_elemento
			";

			$list = $db->query($SQL, "*", 0);
			if( !count($list) ) return false;
			return new ArrayIntList($list);
		}


		public function obtenerAsignadosElementos($string){
			if( !count($this) ) return false;
			$db = db::singleton();


			$list = $this->obtenerIdElementosAsignados(8);
			if( !$list || !count($list) ) return false;

			
			$SQL = "SELECT uid_agrupador FROM ". TABLE_AGRUPADOR ."_elemento 
				INNER JOIN ". TABLE_AGRUPADOR ." USING(uid_agrupador)
				INNER JOIN ". TABLE_AGRUPAMIENTO ."_agrupador aa USING(uid_agrupador)
				INNER JOIN ". TABLE_AGRUPAMIENTO ." ON agrupamiento.uid_agrupamiento = aa.uid_agrupamiento
				WHERE agrupamiento.nombre LIKE '%{$string}%' 
				AND agrupador.uid_empresa IN (
					SELECT uid_empresa FROM ". TABLE_AGRUPADOR ." WHERE uid_agrupador IN ({$this->toComaList()})
				)
				AND uid_modulo = 8 
				AND uid_elemento IN ({$list->toComaList()})
				GROUP BY uid_agrupador
			";

			$asignados = $db->query($SQL, "*", 0, "agrupador");
			if( count($asignados) ){
				return new self($asignados);
			}

			return false;
		}


		public function getAVG(){
			$db = db::singleton();

			$SQL = "
				SELECT AVG(n) FROM (
					SELECT uid_agrupador, count(uid_elemento) n
					FROM ".TABLE_AGRUPADOR."_elemento 
					WHERE uid_modulo = 8
					AND uid_agrupador IN ({$this->toComaList()})
					GROUP BY uid_agrupador 
				) as c
			";
			$AVG = $db->query($SQL, 0, 0);

			if( !$AVG ) return 0;

			$SQL = "
				SELECT uid_agrupador, count(uid_elemento) n
				FROM ".TABLE_AGRUPADOR."_elemento 
				WHERE uid_modulo = 8
				AND uid_agrupador IN ({$this->toComaList()})
				GROUP BY uid_agrupador 
				HAVING count(uid_elemento) > ". $AVG * 5 ."
			";

			$biggest = $db->query($SQL, "*", 0, "agrupador");

			// SI LOS "ENORMES" SON MENOS DE 3, ENTONCES SON EXCEPCIONES Y CALCULAMOS EL VERDADERO AVG
			if( count($biggest) && count($biggest) < 3 ){
				$biggest = new ArrayObjectList($biggest);
				$SQL = "
					SELECT AVG(n) FROM (
						SELECT uid_agrupador, count(uid_elemento) n
						FROM ".TABLE_AGRUPADOR."_elemento 
						WHERE uid_modulo = 8
						AND uid_agrupador IN ({$this->toComaList()})
						AND uid_agrupador NOT IN ({$biggest->toComaList()})
						GROUP BY uid_agrupador 
					) as c
				";
				$AVG = $db->query($SQL, 0, 0);
			}

			return round($AVG);
		}
		
	}
