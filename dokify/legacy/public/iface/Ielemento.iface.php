<?php
	interface Ielemento {

		const EXTRADATA_PARENT = 'parent';
		const EXTRADATA_FILTER = 'filter';

		/**	
		  * Instanciar el elemento o insertar si procede, $extra normalmente es un objeto usuario que nos permite 
		  * guardar un log de cualquier accion
		  * 
		  */
		public function __construct( $param, $extra = false );


		/**	Se encarga de controlar los campos de la tabla para este tipo de elementos
		  *	Debe retornar un objeto de tipo ArrayObject o un array básico, cuyos indices sean arrays
		  *	o FormFields 
		  * 
		  *	Usaremos objetos siempre que sea posible. Se mantendrá el uso de arrays por compatibilidad
		  *
		  *	El formato de array FormField se detalla a continuación:
		  *		tag 		=> String [input, textarea, select, span]
		  *		type		=> String [text, span]
		  *		[blank]		=> Bool [ true, false ] 
		  *		[data]		=> Array · Solo para el tag select
		  *		[size]		=> Int · Longitud del campo
		  *		[match]		=> String · Mascara para el campo
		  *
		  *
		  *	NOTA: Se irán añadiendo a medida que se implementen, es posible que no esten todos aqui
		  */
		public static function publicFields($modo, elemento $objeto = null, Iusuario $usuario = null, $tab = false);


		/** OBTENER INFORMACIÓN DE ESTE ELEMENTO **/
		public function getInfo($publicMode = false, $comeFrom = null, Iusuario $usuario = NULL, $extra = array(), $force = false);

		/**	Nombre visual para este objeto
		  *	
		  */
		public function getUserVisibleName();
	}
?>
