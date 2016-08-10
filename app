#!/usr/bin/php
<?php
	
	require_once('FSM.php');

	$fsm = new FSM();

	while(true)
	{
		$fsm->loop();
		usleep(50000);
	}