<?php
require_once('endeve_base_functions.php');

if(PHP_VERSION>=5) {
	require_once('endeve_base_php5.php');
}else{
	require_once('endeve_base_php4.php');
}
