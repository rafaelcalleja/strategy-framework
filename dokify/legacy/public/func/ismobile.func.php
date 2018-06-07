<?php

	function is_mobile_device () {
		return get_client_version() === 'mobile';
	}