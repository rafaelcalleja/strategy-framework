<?php

	if ($progress = customSession::get('progress')) {

		if ($progress == -1) {
			customSession::set('progress', NULL);
		}

		$dataArray["progress"] = $progress;
	}