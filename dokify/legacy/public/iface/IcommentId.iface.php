<?php
	interface IcommentId {


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
		  * returns Id of the commentId instance
		  *
		  *
		  * @return int
		  *
		  */
		public function getUID();

		/**	
		  * returns the element associated to the commentId
		  *
		  *
		  * @return instance of [empresa|empleado|maquina]
		  *
		  */
		public function getElement();


		/**	
		  * returns the document associated to the commentId
		  *
		  *
		  * @return instance document
		  *
		  */
		public function getDocument();



		/**	
		  * returns the user who commented on this commentId
		  *
		  *
		  * @return instance Iusuario or false
		  *
		  */
		public function getCommenter();


		/**	
		  * returns the uid of the module of the instance of the commenter
		  *
		  *
		  * @return string
		  *
		  */
		public function getCommenterModule();
		

		/**	
		  * returns the text of the comment
		  *
		  *
		  * @return text
		  *
		  */
		public function getComment();


		/**	
		  * returns the action of the comment
		  *
		  *
		  * @return int
		  *
		  */
		public function getAction();

		
		/**	
		  * returns true if the commentId is related with an urgent attachment
		  *
		  *
		  * @return bool
		  *
		  */
		public function isFromUrgent();


		/**	
		  * Add a new comment to the same requirements that tha actual element. Used when reply by email with a commentId
		  *
		  * @param comment text
		  * @param usuario Iusuario
		  * @param action int
		  *
		  * @return instance commentId
		  *
		  */
		public function reply($comment, Iusuario $usuario, $action);

		/**	
		  * returns all the requirements associated with this comment
		  *
		  *
		  * @return arrayObjectList(solicitud)
		  *
		  */
		public function affectTo(Iusuario $user);


		/**	
		  * delete a commentId
		  *
		  *
		  * @return bool
		  *
		  */
		public function deleteComment();


		/**	
		  * edit a commentId
		  *
		  *
		  * @return bool
		  *
		  */
		public function editComment($text);


		/**	
		  * Creates a codified url based on the commentid, user and the enviroment
		  *
		  * @param commentId string
		  * @param user instance of Iusuario
		  * 
		  *
		  * @return string
		  *
		  */
		public static function mountEmailAddress($commentId, Iusuario $user);
	

		/**	
		  * Decode url looking for commentId, userId and the enviroment
		  *
		  * @param Address to decode
		  *
		  *
		  * @return array [commentId, userId, enviroment]
		  *
		  */
		public static function unmountEmailAddress($address);

	}