<?php
	interface IrequirementTypeRequest {


		/**	
		  * returns the user who commented on this commentId
		  * 
		  * @param requirements arrayObjectLis(solicitud)
		  * @param requested instance of an element [empresa|empleado|maquina]
		  *
		  *
		  * @return instance of requirementTypeRequest
		  *
		  */
		public function __construct($requirements, $requested);
		
		/**	
		  * Returns the comment, set of comments or number of comments that match with our filters
		  *
		  * @param usuario instanceof iusuario
		  * @param filter bool or int
		  * @param count bool
		  * @param last bool
		  * @param limit int
		  * @param action int
		  *
		  * @return arrayObjectList(comment) | comment | int | false
		  *
		  */
		public function getComments(Iusuario $usuario = NULL, $filter = false, $count = false, $limit = false);

		/**	
		  * save on comment for each requirement
		  *
		  * @param comment text
		  * @param usuario Iusuario
		  * @param action int
		  * @param assigned int
		  * @param reply bool
		  * @param argument ValidationArgument NULL
		  *
		  *
		  * @return instance commentId
		  *
		  */
		public function saveComment($comment, Iusuario $usuario = NULL, $action = 0, $assigned = false, $reply = false, ValidationArgument $argument = NULL);

	}