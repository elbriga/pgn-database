<?php
class State {
    private $numMove;
    private $originatingMove;
    private $state;
    
    public function __construct($numMove, $originatingMove, $state) {
        $this->numMove         = $numMove;
        $this->originatingMove = $originatingMove;
        $this->state           = $state;
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
