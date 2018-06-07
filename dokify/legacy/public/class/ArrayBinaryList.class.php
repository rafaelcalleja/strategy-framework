<?php
	class ArrayBinaryList extends extendedArray {

		public function getFileTypes () {
			$types = array();
			foreach ($this as $file) {
				$ext = archivo::getExtension($file);
				$types[] = $ext;
			}

			return $types;
		}
	}