<?php

class FSM
{
    const allWays = [
        'Up' => 'goUp',
        'UpRight' => 'goUpRight',
        'Right' => 'goRight',
        'DownRight' => 'goDownRight',
        'Down' => 'goDown',
        'DownLeft' => 'goDownLeft',
        'Left' => 'goLeft',
        'UpLeft' => 'goUpLeft'
    ];
    private $activeState = null;
    private $matrix = null;
    private $matrixSize = null;
    private $objPos = null;
    private $objNewPos = null;
    private $basePos = null;
    private $targetPos = null;
    private $straightWay = null;
    private $straightWayNumStepsToGo = 0;
    private $numOfEat = 0;
    private $numOfMoves = 0;
    private $allreadyPassedWays = [];
    private $notExploredWays = [];

    public function __construct()
    {
        $this->init();
        $this->setState('start');
    }

    public function init()
    {
        $this->matrixSize['y'] = 10;
        $this->matrixSize['x'] = 20;

        for ($y = 0; $y < $this->matrixSize['y']; $y++) {
            for ($x = 0; $x < $this->matrixSize['x']; $x++) {
                $this->matrix[$y][$x] = '0';
            }
        }

        $this->objPos = $this->getRandPos();
        $this->basePos = $this->getRandPos();
        $this->targetPos = $this->getRandPos();

        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '*';
        // $this->matrix[($this->basePos['y'])][($this->basePos['x'])] = '%';
        // $this->matrix[($this->targetPos['y'])][($this->targetPos['x'])] = '^';
    }

    public function getRandPos()
    {
        return ['y' => mt_rand(0, $this->matrixSize['y'] - 1), 'x' => mt_rand(0, $this->matrixSize['x'] - 1)];
    }

    public function setState($state)
    {
        if (method_exists($this, $state)) {
            $this->activeState = $state;
        }
    }

    public function loop()
    {
        $method = $this->activeState;
        $this->$method();
        $this->showMatrix();
    }

    public function showMatrix()
    {
        passthru('tput reset');

        var_dump([
            'numOfEat' => $this->numOfEat,
            'numOfMoves' => $this->numOfMoves,
            'movesWithoutEat' => ($this->numOfMoves - $this->numOfEat)
        ]);

        foreach ($this->matrix as $y) {
            foreach ($y as $x)
                echo $x;
            echo PHP_EOL;
        }
    }

    public function goUp($pos)
    {
        $pos['y'] -= 1;
        $pos['way'] = 'Up';
        return $pos;
    }

    public function goUpRight($pos)
    {
        $pos['y'] = $pos['y'] - 1;
        $pos['x'] = $pos['x'] + 1;
        $pos['way'] = 'UpRight';
        return $pos;
    }

    public function goUpLeft($pos)
    {
        $pos['y'] = $pos['y'] - 1;
        $pos['x'] = $pos['x'] - 1;
        $pos['way'] = 'UpLeft';
        return $pos;
    }

    public function goDown($pos)
    {
        $pos['y'] += 1;
        $pos['way'] = 'Down';
        return $pos;
    }

    public function goDownRight($pos)
    {
        $pos['y'] = $pos['y'] + 1;
        $pos['x'] = $pos['x'] + 1;
        $pos['way'] = 'DownRight';
        return $pos;
    }

    public function goDownLeft($pos)
    {
        $pos['y'] = $pos['y'] + 1;
        $pos['x'] = $pos['x'] - 1;
        $pos['way'] = 'DownLeft';
        return $pos;
    }

    public function goLeft($pos)
    {
        $pos['x'] -= 1;
        $pos['way'] = 'Left';
        return $pos;
    }

    public function goRight($pos)
    {
        $pos['x'] += 1;
        $pos['way'] = 'Right';
        return $pos;
    }

    public function start()
    {
        $this->setState('searchTarget');
    }

    public function deadEnd()
    {
        global $startTime;

        passthru('tput reset');

        var_dump([
            'numOfEat' => $this->numOfEat,
            'numOfMoves' => $this->numOfMoves,
            'movesWithoutEat' => ($this->numOfMoves - $this->numOfEat)
        ]);

        echo 'Game time : ' . (microtime(true) - $startTime) . PHP_EOL;
        echo 'GAME OVER!' . PHP_EOL;
        exit;
    }

    public function searchTarget()
    {
        $this->allreadyPassedWays = [];
        $this->notExploredWays = [];

        foreach (self::allWays as $way => $wayMethod) {
            $possibleWay['y'] = $this->objPos['y'];
            $possibleWay['x'] = $this->objPos['x'];

            $possibleWay = $this->$wayMethod($this->objPos);

            // Try pass by current way
            if ($this->tryPass($possibleWay)) {
                // If already passed by this way...
                if ($this->alreadyPassedHere($possibleWay)) {
                    $this->allreadyPassedWays[] = $possibleWay;
                } // If still not explored this way, try it
                else {
                    $this->notExploredWays [] = $possibleWay;
                }
            } // Maybe still remain another way? Continue searching...
            else {
                continue;
            }
        }

        if (count($this->notExploredWays)) {
            $this->objNewPos = $this->chooseRandWay($this->notExploredWays);

            $this->straightWay = null;
            $this->setState('move');
            return true;
        }

        if ($this->numOfEat == ($this->matrixSize['y'] * $this->matrixSize['x']) - 1) {
            $this->setState('deadEnd');
            return false;
        }

        if (count($this->allreadyPassedWays)) {
            $this->setState('goByStraightWay');
            return true;
        }

        $this->setState('deadEnd');
        return false;
    }

    public function tryPass($way)
    {
        return isset($this->matrix[$way['y']][$way['x']]);
    }

    public function alreadyPassedHere($way)
    {
        return $this->matrix[$way['y']][$way['x']] === '+';
    }

    public function chooseRandWay($ways)
    {
        if (!is_array($ways) || !count($ways))
            return false;

        return $ways[mt_rand(0, count($ways) - 1)];
    }

    public function goByStraightWay()
    {
        // If now no straight way direction, choose it, 
        // and also choose the number of steps you want to walk...

        if (is_null($this->straightWay)) {
            $this->straightWay = $this->chooseRandWay($this->allreadyPassedWays);
            $randAxis = array_rand($this->matrixSize);

            $minMoves = 5;
            $maxMoves = ($this->matrixSize[$randAxis] / 2);
            $this->straightWayNumStepsToGo = mt_rand($minMoves, $maxMoves < $minMoves ? $minMoves : $maxMoves);
        }

        // Keep walking by this way
        $wayMethod = self::allWays[$this->straightWay['way']];

        $this->straightWay = $this->$wayMethod($this->straightWay);

        // If can pass by straight way, continue walking
        if ($this->tryPass($this->straightWay) && $this->straightWayNumStepsToGo > 0) {
            $this->straightWayNumStepsToGo--;
            $this->objNewPos = $this->straightWay;
            $this->setState('move');
            return true;
        } // If can not pass, go back to search way
        else {
            $this->straightWay = null;
            $this->setState('searchTarget');
            return false;
        }
    }

    public function move()
    {
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '+';

        // Eat
        if ($this->matrix[($this->objNewPos['y'])][($this->objNewPos['x'])] === '0') {
            $this->numOfEat++;
        }

        $this->numOfMoves++;

        // Move to new position
        $this->objPos = $this->objNewPos;
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = '*';

        // Continue searching...
        $this->setState('searchTarget');
        return true;
    }
}
