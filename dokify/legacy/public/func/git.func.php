<?php
	
	function git_run ($op) {
		$cmd = "git $op";
		exec($cmd, $out, $ecode);
		if ($ecode == 0) return $out;
		return false;
	}

	function git_get_current_branch () {
		if (!$branches = git_run('branch')) return false;

		foreach ($branches as $branch) {
			if (strpos($branch, "*") !== false) {
				$branch = str_replace('*', '', $branch);
				return trim($branch);
			}
		}

		return $branches;
	}

	function git_get_remote_branches () {
		if (!$branches = git_run('branch -r')) return false;

		$branches = array_map(function ($a){
			$a = str_replace('*', '', $a);
			$pieces = explode('/', trim($a));
			return trim(end($pieces));
		}, $branches);

		$branches = array_unique($branches);
		return $branches;
	}

	function git_get_branches () {
		if (!$branches = git_run('branch')) return false;

		$branches = array_map(function ($a){
			$a = str_replace('*', '', $a);
			return trim($a);
		}, $branches);

		return $branches;
	}

	function git_get_root () {
		$cmd = "git rev-parse --show-toplevel";
		exec($cmd, $out, $ecode);
		if ($ecode == 0) return trim($out[0]);
		return false;
	}

	function git_get_head_date () {
		if ($output = git_run("log -1 --format=%cd")) {
			return $output[0];
		}
	}

	function git_get_head () {
		if ($output = git_run("rev-parse HEAD")) {
			return $output[0];
		}
	}

	function git_pull ($param) {
		return git_run('pull '. $param);
	}

	function git_clean () {
		return git_run('clean -f -d');
	}

	function git_checkout ($branch) {
		return git_run('checkout '. $branch .' --force');
	}