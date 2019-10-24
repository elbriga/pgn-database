<?php
class Piece {
    public $color, $type;
    public $position;
    public $index;
    public $distanceMoved; // on the last move attempt
    
    private static function asciisum($leter, $add) {
        return chr(ord($leter) + $add);
    }
    
    public function __construct($color, $type, $position) {
        $this->color    = $color;
        $this->type     = $type;
        $this->position = $position;
    }
    
    /**
     * check if this piece can move to $targetPosition
     * @param string $targetPosition
     * @param boolean $taking - the move is a taking! X
     * @return number distance moved (0 = cant move!)
     */
    public function canMoveTo($targetPosition, $taking) {
        return ($this->distanceMoved = $this->__canMoveTo($targetPosition, $taking));
    }
    
    private function __canMoveTo($targetPosition, $taking) {
        $col = $targetPosition[0]; // leter
        $row = $targetPosition[1]; // number
        switch($this->type) {
            case 'Q':
            case 'K': // There is only one!
                return 1;

            case 'P': // Pawn
                $direction = ($this->color == 'W') ? -1 : 1;
                if($taking) {
                    // coming from the left
                    if($this->position == self::asciisum($col, -1) . ($row + $direction)) {
                        return 1;
                    }
                    // coming from the right
                    if($this->position == self::asciisum($col,  1) . ($row + $direction)) {
                        return 1;
                    }
                } else {
                    // basic pawn move
                    if($this->position == $col . ($row + $direction)) {
                        return 1;
                    }
                    // inital 2 steps pawn move
                    if((($this->color=='W' && $row==4) || ($this->color=='B' && $row==5)) && $this->position == $col . ($row + ($direction * 2))) {
                        return 2;
                    }
                }
                break;
                
            case 'N': // kNight
                if(
                    ($this->position == self::asciisum($col, -1) . ($row - 2)) ||
                    ($this->position == self::asciisum($col, -2) . ($row - 1)) ||
                    ($this->position == self::asciisum($col, -2) . ($row + 1)) ||
                    ($this->position == self::asciisum($col, -1) . ($row + 2)) ||
                    ($this->position == self::asciisum($col,  1) . ($row - 2)) ||
                    ($this->position == self::asciisum($col,  2) . ($row - 1)) ||
                    ($this->position == self::asciisum($col,  2) . ($row + 1)) ||
                    ($this->position == self::asciisum($col,  1) . ($row + 2))
                ) {
                    return 3;
                }
                break;
                
            case 'B': // Bishop
                for($step=1; $step<8; $step++) {
                    if(
                        ($this->position == self::asciisum($col, -1*$step) . ($row - $step)) ||
                        ($this->position == self::asciisum($col,    $step) . ($row - $step)) ||
                        ($this->position == self::asciisum($col,    $step) . ($row + $step)) ||
                        ($this->position == self::asciisum($col, -1*$step) . ($row + $step))
                        ) {
                            return $step;
                        }
                }
                break;
                
            case 'R': // Rook
                for($step=1; $step<8; $step++) {
                    if(
                        ($this->position == $col . ($row + $step)) ||
                        ($this->position == $col . ($row - $step)) ||
                        ($this->position == self::asciisum($col,    $step) . $row) ||
                        ($this->position == self::asciisum($col, -1*$step) . $row)
                        ) {
                            return $step;
                        }
                }
                break;
        }
        
        return 0;
    }
}
