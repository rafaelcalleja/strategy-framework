<?php
function text2html($text){
	$text = nl2br($text);
	$text = str_replace("\t", "&nbsp;&nbsp;", $text);
	return $text;
}
?>