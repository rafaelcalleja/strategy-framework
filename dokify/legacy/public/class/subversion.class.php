<?php
	class subversion {
		const REPO = "http://svn.afianza.net/agd/";

		public static function getLog($num=10){
			$tmpfile = "/tmp/svnlog_". uniqid() .".log";
			exec("svn log -l $num --xml --with-all-revprops --verbose ". subversion::REPO ." --username jandres --password jandres > $tmpfile");
			$data = xml2array( utf8_encode(file_get_contents($tmpfile)) );
			@unlink($tmplfile);
			return $data["log"]["logentry"];
		}

		public static function getLastRevisionNumber(){
			$tmpfile = "/tmp/svnlog_". uniqid() .".log";
			$command = "svn info --xml ". subversion::REPO ." --username jandres --password jandres > $tmpfile";
			exec($command);
			$data = xml2array( utf8_encode(file_get_contents($tmpfile)) );
			return $data["info"]["entry_attr"]["revision"];
		}
	}
?>
