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
        return ($this->distanceMoved = $this->__canMoveTo($targetPosition, $taking, null));
    }
    
    private function __canMoveTo($targetPosition, $taking, $pieceType=null) {
        $col = $targetPosition[0]; // leter
        $row = $targetPosition[1]; // number

        $type = ($pieceType === null) ? $this->type : $pieceType;
        switch($type) {
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
                    // initial 2 steps pawn move
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
                    return 1; // Consider distance 1 to all knight's moves for canMoveThrought to work
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
                if(($step = $this->__canMoveTo($targetPosition, $taking, 'B')) > 0) {
                    // Queen can move as a Bishop!
                    return $step;
                }
                if(($step = $this->__canMoveTo($targetPosition, $taking, 'R')) > 0) {
                    // Queen can move as a Rook!
                    return $step;
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
        return $this->__canMoveThrought($targetPosition, $board, null);
    }
    
    private function __canMoveThrought($targetPosition, Board $board, $pieceType=null) {
        if($this->distanceMoved === 1) return true;
        
        $col = $targetPosition[0]; // leter
        $row = $targetPosition[1]; // number

        $type = ($pieceType === null) ? $this->type : $pieceType;
        switch($type) {
            case 'P': // Pawn
                // initial 2 steps pawn move
                if($board->getPieceAt($col . ($this->color=='W' ? 3 : 6))) {
                    return false;
                }
                return true;
                
            case 'B': // Bishop
                $stepC = ($col > $this->position[0]) ? 1 : -1;
                $stepR = ($row > $this->position[1]) ? 1 : -1;
                for($d=1; $d<$this->distanceMoved; $d++) {
                    if($board->getPieceAt(self::asciisum($this->position[0], ($d * $stepC)) . ($this->position[1] + ($d * $stepR)))) {
                        return false;
                    }
                }
                return true;
                
            case 'R': // Rook
                if($this->position[0] == $col) {
                    // Moving vertically
                    $step = ($row > $this->position[1]) ? 1 : -1;
                    for($r=$this->position[1]+$step; $r!=$row; $r += $step) {
                        if($board->getPieceAt($col . $r)) {
                            return false;
                        }
                    }
                } else if($this->position[1] == $row) {
                    // Moving horizontally
                    $step = ($col > $this->position[0]) ? 1 : -1;
                    for($c=self::asciisum($this->position[0], $step); $c!=$col; $c = self::asciisum($c, $step)) {
                        if($board->getPieceAt($c . $row)) {
                            return false;
                        }
                    }
                } else {
                    return false;
                }
                return true;
                
            case 'Q': // Queen
                if($this->__canMoveThrought($targetPosition, $board, 'B')) {
                    // Queen can move as a Bishop!
                    return true;
                }
                if($this->__canMoveThrought($targetPosition, $board, 'R')) {
                    // Queen can move as a Rook!
                    return true;
                }
                break;
                
            // Should never get here, because distanceMoved for those 2 are always 1
            case 'N': // kNight
            case 'K': // King
                return true;
        }
        
        return false;
    }
    
    /**
     * check if this piece is checking the King
     * @param Piece $king
     * @param Board $board
     * @return boolean
     */
    public function isCheking(Piece $king, Board $board) {
        if($this->type == 'K') return false; // Kings never check!
        
        return $this->canMoveTo($king->position, true) && $this->canMoveThrought($king->position, $board);
    }
    
    /**
     * check if this piece is pinned protecting the King
     * @param Board $board
     * @return boolean
     */
    public function isPinned(Board $board) {
        // Calculate total checks WITH the piece on the board
        $totChecksW = $board->totalChecks($this->color);

        // Calculate total checks WITHOUT the piece on the board
        $boardWO = clone $board;
        $boardWO->removePiece($this);
        $totChecksWO = $boardWO->totalChecks($this->color);
        
        // if totWO > totW the piece is pinned!
        return ($totChecksWO > $totChecksW);
    }
}
