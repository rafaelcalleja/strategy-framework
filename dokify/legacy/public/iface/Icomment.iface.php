<?php
	interface Icomment {


		/**	
		  * returns the user who commented on this commentId
		  * 
		  * @param commentId string
		  *
		  *
		  * @return instance of commentId
		  *
		  */
		public function __construct($commentId);
		

		/**	
		  *	return the text of a comment
		  *
		  * @return text
		  *
		  */
		public function getComment();

		/**	
		  * returns the element associated to this comment
		  *
		  *
		  * @return instance of [empresa|empleado|maquina]
		  *
		  */
		public function getElement();


		/**	
		  * returns the user who commented
		  *
		  *
		  * @return instance Iusuario or false
		  *
		  */
		public function getCommenter();


		/**	
		  * returns the attribute associated to the comment
		  *
		  *
		  * @return instance documento_atributo
		  *
		  */
		public function getAttribute();


		/**	
		  * returns the action of the comment
		  *
		  *
		  * @return int
		  *
		  */
		public function getAction();


		/**	
		  * returns the date where the comment were done
		  *
		  *
		  * @return time
		  *
		  */
		public function getDate();


		/**	
		  * returns the id of the comment
		  *
		  *
		  * @return string
		  *
		  */
		public function getCommentId();


		/**	
		  * returns the uid of the grouping(agrupador) associated with the comment
		  *
		  * @return instance agrupador or false
		  *
		  */
		public function getAgrupadorReferencia();


		/**	
		  * returns the uid of the grouping(agrupador) associated with the comment
		  *
		  * @return instance of empresa, arrayObjectList(empresa) or false
		  *
		  */
		public function getEmpresaReferencia();

	}