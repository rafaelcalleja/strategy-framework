<?php
	file_put_contents('/tmp/calls.log', json_encode($_REQUEST) . "\n", FILE_APPEND);