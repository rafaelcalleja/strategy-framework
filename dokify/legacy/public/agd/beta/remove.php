<?php

	require '../../api.php';

	if ($usuario->setBetatester(false)) {
		header("Location: /agd");
	}