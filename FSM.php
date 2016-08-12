
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

    const filledObject = [
        'Obstruction',
        'Food'
    ];

    private $objectTypes = [];

    private $activeState = null;
    private $matrix = null;
    private $matrixSize = [
        'y' => 20,
        'x' => 50
    ];
    private $objPos = null;
    private $objNewPos = null;
    private $objPrevPos = null;
    private $straightWay = null;
    private $straightWayNumStepsToGo = 0;
    private $numOfMeals = 0;
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
        $this->objectTypes = [
            'Object' => ['char' => '*'],
            'AlreadyPassed' => ['char' => '.'],
            'Empty' => ['char' => chr(32)],
            'Obstruction' => ['char' => chr(176), 'chance' => 50],
            'Food' => ['char' => '@', 'chance' => 50]
        ];

        // Fill the matrix
        for ($y = 0; $y < $this->matrixSize['y']; $y++) {
            for ($x = 0; $x < $this->matrixSize['x']; $x++) {
                $fillObjKey = mt_rand(0, count(self::filledObject) - 1);
                $fillObjName = self::filledObject[$fillObjKey];

                if (isset($this->objectTypes[$fillObjName]['chance'])
                    && $this->getChance($this->objectTypes[$fillObjName]['chance'])
                ) {
                    if (!isset($this->objectTypes[$fillObjName]['count'])) {
                        $this->objectTypes[$fillObjName]['count'] = 0;
                    }

                    $this->objectTypes[$fillObjName]['count']++;
                    $this->matrix[$y][$x] = $this->objectTypes[$fillObjName]['char'];
                } else {
                    $this->matrix[$y][$x] = $this->objectTypes['Empty']['char'];
                }
            }
        }
        // Set object position
        $this->objPos = $this->getRandPos();
        $this->objPrevPos = $this->objPos;
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = $this->objectTypes['Object']['char'];
    }

    function getChance($percent)
    {
        return mt_rand(0, 99) < $percent;
    }

    public function getRandPos()
    {
        return [
            'y' => mt_rand(0, $this->matrixSize['y'] - 1),
            'x' => mt_rand(0, $this->matrixSize['x'] - 1)
        ];
    }

    public function setState($state)
    {
        if (method_exists($this, $state)) {
            $this->activeState = $state;
        }
    }

    public function chooseRandKey()
    {
        return ['y' => mt_rand(0, $this->matrixSize['y'] - 1), 'x' => mt_rand(0, $this->matrixSize['x'] - 1)];
    }

    public function loop()
    {
        $method = $this->activeState;
        $this->$method();
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
        $this->drawMatrix();
       $this->setState('searchTarget');
    }

    public function drawMatrix()
    {
        passthru('tput reset');
        passthru('tput civis');

        $this->drawRect(
            [
                'y' => 0,
                'x' => 0
            ],
            [
                'y' => $this->matrixSize['y'] + 1,
                'x' => $this->matrixSize['x'] + 1
            ], '.', 3, 4);

        passthru('tput cup 1 1');

        for ($y = 0; $y < $this->matrixSize['y']; $y++) {
            for ($x = 0; $x < $this->matrixSize['x']; $x++) {
                echo $this->matrix[$y][$x];
            }
            passthru('tput cup ' . ($y + 2) . ' 1');
        }
    }

    public function drawRect($p1, $p2, $char = '.', $fColor = 7, $bColor = 0)
    {
        passthru('tput setaf ' . $fColor);
        passthru('tput setab ' . $bColor);

        for ($i = 0; $i <= $p2['x']; $i++) {
            passthru('tput cup 0 ' . $i);
            echo $char;

            passthru('tput cup ' . $p2['y'] . ' ' . $i);
            echo $char;
        }

        for ($i = 0; $i <= $p2['y']; $i++) {
            passthru('tput cup ' . $i . ' ' . $p1['x']);
            echo $char;

            passthru('tput cup ' . $i . ' ' . $p2['x']);
            echo $char;
        }

        passthru('tput setaf ' . 7);
        passthru('tput setab ' . 0);

        passthru('tput cup 0 0');
    }

    public function deadEnd()
    {
        global $startTime;

        $this->drawMatrix();

        var_dump([
            'numOfMeals' => $this->numOfMeals,
            'numOfMoves' => $this->numOfMoves,
            'movesWithoutMeals' => ($this->numOfMoves - $this->numOfMeals)
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

        if ($this->numOfMeals == $this->objectTypes['Food']['count']) {
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
        return
            isset($this->matrix[$way['y']][$way['x']])
            && ($this->matrix[$way['y']][$way['x']] !== $this->objectTypes['Obstruction']['char']);
    }

    public function alreadyPassedHere($way)
    {
        return $this->matrix[$way['y']][$way['x']] === $this->objectTypes['AlreadyPassed']['char'];
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

            $minMoves = 3;
            $maxMoves = ($this->matrixSize[$randAxis] / 2);
            $this->straightWayNumStepsToGo = mt_rand($minMoves, $maxMoves < $minMoves ? $minMoves : $maxMoves);
        } else {
            // Keep walking by this way
            $wayMethod = self::allWays[$this->straightWay['way']];
            $this->straightWay = $this->$wayMethod($this->straightWay);
        }

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
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = $this->objectTypes['AlreadyPassed']['char'];

        // Meal
        if ($this->matrix[($this->objNewPos['y'])][($this->objNewPos['x'])] === $this->objectTypes['Food']['char']) {
            $this->numOfMeals++;
        }

        $this->numOfMoves++;

        // Remember old position
        $this->objPrevPos = $this->objPos;

        // Move to new position

        $this->objPos = $this->objNewPos;
        $this->matrix[($this->objPos['y'])][($this->objPos['x'])] = $this->objectTypes['Object']['char'];

        // Redraw
        $this->drawMove();

        // Continue searching...
        $this->setState('searchTarget');
        return true;
    }

    public function drawMove()
    {
        passthru('tput cup ' . ($this->objPrevPos['y'] + 1) . ' ' . ($this->objPrevPos['x'] + 1));
        echo $this->matrix[$this->objPrevPos['y']][$this->objPrevPos['x']];

        passthru('tput cup ' . ($this->objPos['y'] + 1) . ' ' . ($this->objPos['x'] + 1));
        echo $this->matrix[$this->objPos['y']][$this->objPos['x']];
    }
}
