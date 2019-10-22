<?php
class Piece {
    public $color, $type, $position;
    
    public function __construct($color, $type, $position) {
        $this->color    = $color;
        $this->type     = $type;
        $this->position = $position;
    }
    
    public function canMoveTo($position, $take=false) {
        switch($this->type) {
            case 'P': // Pawn
                if($this->color == 'W') {
                    
                } else {
                    
                }
                break;
        }
    }
}
