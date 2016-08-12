#!/usr/bin/php
<?php

require_once('FSM.php');

$fsm = new FSM();

$startTime = microtime(true);

while (true) {
    $fsm->loop();
    // usleep(50000);
}