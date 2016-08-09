<?php

class FSM {
    private $activeState = null;
    private $matrix = null;
    private $matrixSize = null;
    private $objPos = null;
    private $basePos = null;
    private $targetPos = null;
    private $objNewPos = null;

    public function __construct() {
        $this->init();
    	$this->setState('start');
    }
 
    public function setState($state) {
    	if (method_exists($this, $state)) {
        	$this->activeState = $state;
        }
    }
 
    public function loop() {
    	$method = $this->activeState;
		$this->$method();
        $this->showMatrix();
    }
    
    public function init() {
        
        $this->matrixSize['y'] = 5;
        $this->matrixSize['x'] = 5;

        for($y=0; $y<$this->matrixSize['y'];$y++)
        {
            for($x=0; $x<$this->matrixSize['x'];$x++)
            {
                $this->matrix[$y][$x] = 0;
            }
        }

        $this->objPos = $this->getRandPos();
        $this->basePos = $this->getRandPos();
        $this->targetPos = $this->getRandPos();

        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '*';
        $this->matrix[($this->basePos['y'])][($this->basePos['x'])] = '%';
        $this->matrix[($this->targetPos['y'])][($this->targetPos['x'])] = '^';
    }

    public function showMatrix() {
        
        passthru('tput reset');

        foreach($this->matrix as $y)
        {
            foreach($y as $x)
                echo $x;
            echo PHP_EOL;
        }
    }

    public function getRandPos() {
        return ['y' => mt_rand(0, $this->matrixSize['y']-1), 'x' => mt_rand(0, $this->matrixSize['x']-1)];
    }

    public function tryPass($y, $x) {
        return isset($this->matrix[$y][$x]);
    }

    public function alreadyPassedHere($y, $x) {
        return $this->matrix[$y][$x] === '+';
    }

    public function chooseRandWay($ways) {
        if(!is_array($ways) || !count($ways))
            return false;

        return $ways[mt_rand(0, count($ways) - 1)];
    }

    public function start() {
        $this->setState('searchTarget');
    }

    public function deadEnd() {
        echo 'GAME OVER!' . PHP_EOL;
        exit;
    }

    public function searchTarget() {
        $allWays = 7;
        $allreadyPassedWays = [];
        $notExploredWays = [];

        for($i=0;$i<=$allWays;$i++)
        {
            $objNewPos['y'] = $this->objPos['y'];
            $objNewPos['x'] = $this->objPos['x'];

            switch($i)
            {
                case 0:
                    $objNewPos['y'] = $this->objPos['y'] - 1;
                    $objNewPos['x'] = $this->objPos['x'];
                    break;

                case 1:
                    $objNewPos['y'] = $this->objPos['y'] - 1;
                    $objNewPos['x'] = $this->objPos['x'] + 1;
                    break;

                case 2:
                    $objNewPos['y'] = $this->objPos['y'];
                    $objNewPos['x'] = $this->objPos['x'] + 1;
                    break;

                case 3:
                    $objNewPos['y'] = $this->objPos['y'] + 1;
                    $objNewPos['x'] = $this->objPos['x'] + 1;
                    break;

                case 4:
                    $objNewPos['y'] = $this->objPos['y'] + 1;
                    $objNewPos['x'] = $this->objPos['x'];
                    break;

                case 5:
                    $objNewPos['y'] = $this->objPos['y'] + 1;
                    $objNewPos['x'] = $this->objPos['x'] - 1;
                    break;

                case 6:
                    $objNewPos['y'] = $this->objPos['y'];
                    $objNewPos['x'] = $this->objPos['x'] - 1;
                    break;

                case 7:
                    $objNewPos['y'] = $this->objPos['y'] - 1;
                    $objNewPos['x'] = $this->objPos['x'] - 1;
                    break;
            }

            // Try pass by current way
            if($this->tryPass($objNewPos['y'], $objNewPos['x'])) {
                // If already passed by this way...
                if($this->alreadyPassedHere($objNewPos['y'], $objNewPos['x'])) {
                    $allreadyPassedWays[] = $objNewPos;
                }
                // If still not explored this way, try it
                else {
                    $notExploredWays [] = $objNewPos;
                }
            }
            // Maybe still remain another way? Continue searching...
            else {
                continue;
            }
        }

        // DEBUG
        
        // file_put_contents('debug.log',
        //     json_encode(['allreadyPassedWays' => $allreadyPassedWays]) . PHP_EOL, 
        //     FILE_APPEND);

        // file_put_contents('debug.log',
        //     json_encode(['notExploredWays' => $notExploredWays]) . PHP_EOL, 
        //     FILE_APPEND);
        
        ///

        if(count($notExploredWays))
        {
            $this->objNewPos = $this->chooseRandWay($notExploredWays);
            
             // DEBUG //

        // file_put_contents('debug.log',
        //     json_encode(['moveFrom' => $this->objPos]) . PHP_EOL, 
        //     FILE_APPEND);

        file_put_contents('debug.log',
            json_encode(['moveTo' => $this->objNewPos]) . PHP_EOL, 
            FILE_APPEND);
        ///

            $this->setState('move');
            return true;
        }
        
        if(count($allreadyPassedWays))
        {
            $this->objNewPos = $this->chooseRandWay($allreadyPassedWays);
            $this->setState('move');
            return true;
        }
        
        $this->setState('deadEnd');
        return false;

    }
    
    public function move()
    {
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '+';

        $this->objPos = $this->objNewPos;
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '*';

        $this->setState('searchTarget');
        return true;
    }
}