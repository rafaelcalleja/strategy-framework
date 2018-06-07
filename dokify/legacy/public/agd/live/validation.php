<?php
	$currentFileId = (isset($_SESSION["CURRENT_FILEID"])) ? $_SESSION["CURRENT_FILEID"] : false;
	if ($currentFileId) {
		if ($module = fileId::getModuleOfFileId($currentFileId)) {
			$fileId = new fileId($currentFileId, $module);
			if ($fileId instanceof fileId) {
				$assigned = $fileId->isOtherAssigned($usuario);
				if ($assigned) {
					unset($_SESSION["CURRENT_FILEID"]);
					$dataArray["otherUserAssigned"] = true;
				}
			}
		}
	}
