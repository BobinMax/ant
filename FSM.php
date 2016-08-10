<?php

class FSM {
    private $activeState = null;
    private $matrix = null;
    private $matrixSize = null;
    private $objPos = null;
    private $basePos = null;
    private $targetPos = null;
    private $possibleWay = null;
    private $straightWay = null;
    private $numOfNewWays = 0;
    private $numOfAlreadyPassedWays = 0;
    private $allreadyPassedWays = [];
    private $notExploredWays = [];
    
    const allWays = [
        'Up'        => 'goUp',
        'UpRight'   => 'goUpRight', 
        'Right'     => 'goRight',
        'DownRight' => 'goDownRight',
        'Down'      => 'goDown',
        'DownLeft'  => 'goDownLeft',
        'Left'      => 'goLeft',
        'UpLeft'    => 'goUpLeft'
        ];

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
        
        $this->matrixSize['y'] = 25;
        $this->matrixSize['x'] = 25;

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
        var_dump([
            'numOfNewWays' => $this->numOfNewWays,
            'numOfAlreadyPassedWays' => $this->numOfAlreadyPassedWays
        ]);

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

    public function tryPass($way) {
        return isset($this->matrix[$way['y']][$way['x']]);
    }

    public function alreadyPassedHere($way) {
        return $this->matrix[$way['y']][$way['x']] === '+';
    }

    public function chooseRandWay($ways) {
        if(!is_array($ways) || !count($ways))
            return false;

        return $ways[mt_rand(0, count($ways) - 1)];
    }

    public function goUp($pos) {
        $pos['y'] -= 1;
        $pos['way'] = 'Up';
        return $pos;
    }

    public function goUpRight($pos) {
        $pos['y'] = $pos['y'] - 1;
        $pos['x'] = $pos['x'] + 1;
        $pos['way'] = 'UpRight';
        return $pos;
    }

    public function goUpLeft($pos) {
        $pos['y'] = $pos['y'] - 1;
        $pos['x'] = $pos['x'] - 1;
        $pos['way'] = 'UpLeft';
        return $pos;
    }

    public function goDown($pos) {
        $pos['y'] += 1;
        $pos['way'] = 'Down';
        return $pos;
    }

    public function goDownRight($pos) {
        $pos['y'] = $pos['y'] + 1;
        $pos['x'] = $pos['x'] + 1;
        $pos['way'] = 'DownRight';
        return $pos;
    }

    public function goDownLeft($pos) {
        $pos['y'] = $pos['y'] + 1;
        $pos['x'] = $pos['x'] - 1;
        $pos['way'] = 'DownLeft';
        return $pos;
    }

    public function goLeft($pos) {
        $pos['x'] -= 1;
        $pos['way'] = 'Left';
        return $pos;
    }

    public function goRight($pos) {
        $pos['x'] += 1;
        $pos['way'] = 'Right';
        return $pos;
    }

    public function start() {
        $this->setState('searchTarget');
    }

    public function deadEnd() {
        global $startTime;
        
        passthru('tput reset');

        var_dump([
            'numOfNewWays' => $this->numOfNewWays,
            'numOfAlreadyPassedWays' => $this->numOfAlreadyPassedWays
        ]);

        echo 'Game time : ' . (microtime(true) - $startTime) . PHP_EOL;
        echo 'GAME OVER!' . PHP_EOL;
        exit;
    }

    public function searchTarget() {
        $this->allreadyPassedWays = [];
        $this->notExploredWays = [];

        foreach(self::allWays as $way => $wayMethod)
        {
            $possibleWay['y'] = $this->objPos['y'];
            $possibleWay['x'] = $this->objPos['x'];
            
            $possibleWay = $this->$wayMethod($this->objPos);

            // Try pass by current way
            if($this->tryPass($possibleWay)) {
                // If already passed by this way...
                if($this->alreadyPassedHere($possibleWay)) {
                    $this->allreadyPassedWays[] = $possibleWay;
                }
                // If still not explored this way, try it
                else {
                    $this->notExploredWays [] = $possibleWay;
                }
            }
            // Maybe still remain another way? Continue searching...
            else {
                continue;
            }
        }

        if(count($this->notExploredWays))
        {
            $this->numOfNewWays++;
            $this->objNewPos = $this->chooseRandWay($this->notExploredWays);

            $this->straightWay = null;
            $this->setState('move');
            return true;
        }

        if($this->numOfNewWays == ($this->matrixSize['y'] * $this->matrixSize['x']) - 1) {
            $this->setState('deadEnd');
            return false;
        }

        if(count($this->allreadyPassedWays))
        {
            $this->numOfAlreadyPassedWays++;
            $this->setState('goByStraightWay');
            return true;
        }
        
        $this->setState('deadEnd');
        return false;
    }
    
    public function goByStraightWay()
    {
        if(is_null($this->straightWay))
            $this->straightWay = $this->chooseRandWay($this->allreadyPassedWays);

        // Keep walking by this way
        $wayMethod = self::allWays[$this->straightWay['way']];

        $this->straightWay = $this->$wayMethod($this->straightWay);

        if($this->tryPass($this->straightWay)) {
            $this->objNewPos = $this->straightWay;
            $this->setState('move');
            return true;
        }
        else {
            $this->straightWay = null;
            $this->setState('searchTarget');
            return false;
        }
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
