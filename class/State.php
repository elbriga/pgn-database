<?php
class State {
    private $numMove;
    private $originatingMove;
    private $state;
    
    public function __construct($numMove, $originatingMove, Board $board) {
        $this->numMove         = $numMove;
        $this->originatingMove = $originatingMove;
        
        // Generate FEN string from board
        $blanks = 0;
        $lines  = [];
        $line   = '';
        for($row=8; $row>0; $row--) {
            for($col='a'; $col<='h'; $col++) {
                $piece = $board->getPieceAt($col.$row);
                if(!$piece) {
                    $blanks++;
                } else {
                    if($blanks > 0) {
                        $line  .= $blanks;
                        $blanks = 0;
                    }
                    $line .= ($piece->color == 'B') ? strtolower($piece->type) : $piece->type;
                }
            }
            if($blanks > 0) {
                $line  .= $blanks;
                $blanks = 0;
            }
            $lines[] = $line;
            $line    = '';
        }
        
        $this->state = implode('/', $lines);
    }
    
    public function getInsertSQL($idMatch) {
        $move = strstr($this->originatingMove, '.') ? explode('.', $this->originatingMove)[1] : $this->originatingMove;
        return "INSERT INTO boardstate(idmatch,nummove,origmove,state) VALUES ($idMatch, $this->numMove, '$move', '$this->state')";
    }
    
    public function __toString() {
        $ret = $this->state . ":\n";
        foreach(explode('/', $this->state) as $line) {
            for($c=0; $c < strlen($line); $c++) {
                $ret .= is_numeric($line[$c]) ? str_repeat('# ', $line[$c]) : $line[$c].' ';
            }
            $ret .= "\n";
        }
        return $ret;
    }
}
