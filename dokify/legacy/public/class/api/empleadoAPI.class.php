<?php

	class empleadoAPI extends empleado {

		public function get(){
			return array("id" => $this->uid);
		}
	}
