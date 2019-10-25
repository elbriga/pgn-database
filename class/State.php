<?php
class State {
    private $numMove;
    private $originatingMove;
    private $state;
    
    public function __construct($numMove, $originatingMove, Board $board) {
        $this->numMove         = $numMove;
        $this->originatingMove = $originatingMove;
        
        // Generate state string from board
        $this->state = '';
        for($row=8; $row>0; $row--) {
            for($col='a'; $col<='h'; $col++) {
                $piece = $board->getPieceAt($col.$row);
                if(!$piece) {
                    $char = '#';
                } else if($piece->color == 'B') {
                    $char = strtolower($piece->type);
                } else {
                    $char = $piece->type;
                }
                $this->state .= $char;
            }
        }
    }
    
    public function getInsertSQL($idMatch) {
        return "INSERT INTO boardstate(idmatch,nummove,origmove,state) VALUES ($idMatch, $this->numMove, '$this->originatingMove', '$this->state')";
    }
    
    public function __toString() {
        $ret = '';
        for($l=0; $l < 8; $l++) {
            for($c=0; $c < 8; $c++) {
                $ret .= $this->state[($l * 8) + $c];
                if($c != 7) $ret .= ' ';
            }
            $ret .= "\n";
        }
        return $ret;
    }
}
