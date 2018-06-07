<?php

	require '../../api.php';

	if ($usuario->setBetatester(true)) {
		header("Location: /agd");
	}