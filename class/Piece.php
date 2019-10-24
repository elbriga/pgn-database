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
     * @return integer distance moved (0 = cant move!)
     */
    public function canMoveTo($targetPosition, $taking) {
        return ($this->distanceMoved = $this->__canMoveTo($targetPosition, $taking));
    }
    
    private function __canMoveTo($targetPosition, $taking) {
        $col = $targetPosition[0]; // leter
        $row = $targetPosition[1]; // number

        switch($this->type) {
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

            case 'Q': // Queen
                for($step=1; $step<8; $step++) {
                    if(
                        ($this->position == self::asciisum($col, -1*$step) . ($row - $step)) || // Diagonal
                        ($this->position == self::asciisum($col,    $step) . ($row - $step)) ||
                        ($this->position == self::asciisum($col,    $step) . ($row + $step)) ||
                        ($this->position == self::asciisum($col, -1*$step) . ($row + $step)) ||
                        
                        ($this->position == $col . ($row + $step)) || // Vertical
                        ($this->position == $col . ($row - $step)) ||
                        ($this->position == self::asciisum($col,    $step) . $row) || // Horizontal
                        ($this->position == self::asciisum($col, -1*$step) . $row)
                        ) {
                            return $step;
                        }
                }
                break;

            case 'K': // There is only one!
                return 1;
        }
        
        return 0;
    }
    
    /**
     * check if there is no pieces on the way
     * assumes that 'canMoveTo' has already been called
     * (uses 'distanceMoved' setted there)
     * @param string $targetPosition
     * @param Board $board
     * @return boolean
     */
    public function canMoveThrought($targetPosition, Board $board) {
        $col = $targetPosition[0]; // leter
        $row = $targetPosition[1]; // number

        switch($this->type) {
            case 'N': // kNight
            case 'K': // King
                return true;

            case 'P': // Pawn
                return ($this->distanceMoved === 1);
                
            case 'B': // Bishop
                if($this->distanceMoved === 1) return true;
                break;
                
            case 'R': // Rook
                if($this->distanceMoved === 1) return true;
                if($this->position[0] == $col) {
                    // Moving vertically
                    $step = ($row > $this->position[1]) ? 1 : -1;
                    for($r=$this->position[1]+$step; $r!=$row; $r += $step) {
                        if($board->getPieceAt($col . $r)) {
                            return false;
                        }
                    }
                } else {
                    // Moving horizontally
                    $step = ($col > $this->position[0]) ? 1 : -1;
                    for($c=self::asciisum($this->position[0], $step); $c!=$col; $c = self::asciisum($c, $step)) {
                        if($board->getPieceAt($c . $row)) {
                            return false;
                        }
                    }
                }
                return true;
                
            case 'Q': // Queen
                if($this->distanceMoved === 1) return true; // TODO
                break;
        }
        
        return false;
    }
}
