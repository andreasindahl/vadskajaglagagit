<?php
	
	require_once 'core/init.php';

	if (Input::exists()) {
		$rating = Input::get('rating');
		if ($rating >= 1 && $rating <= 5) {
			if (Rating::rate(Input::get('recipe'), $rating)) {
				echo 1;
				exit();
			}
		}
	}
	echo 0;