<?php
	
	require dirname(__FILE__) . '/api.php';

	if ($usuario->esAdministrador()) {
		$cmd = @$_GET['c'];
		$branch = @$_GET['b'];

		if ($cmd && $branch) {
			$fn = 'git_' . $cmd;

			call_user_func($fn, $branch);

			git_clean();
			git_pull("origin $branch");
		}
	}

	header('Location: /agd/');