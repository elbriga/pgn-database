<?php
class Board {
    private $pieces = [];
    
    public function __construct() {
        // Pawns
        for($col='a'; $col<='h'; $col++) {
            $this->addPiece(new Piece('W', 'P', $col.'2'));
            $this->addPiece(new Piece('B', 'P', $col.'7'));
        }
        
        // Rooks
        $this->addPiece(new Piece('W', 'R', 'a1'));
        $this->addPiece(new Piece('W', 'R', 'h1'));
        $this->addPiece(new Piece('B', 'R', 'a8'));
        $this->addPiece(new Piece('B', 'R', 'h8'));
        
        // kNights
        $this->addPiece(new Piece('W', 'N', 'b1'));
        $this->addPiece(new Piece('W', 'N', 'g1'));
        $this->addPiece(new Piece('B', 'N', 'b8'));
        $this->addPiece(new Piece('B', 'N', 'g8'));
        
        // Bishops
        $this->addPiece(new Piece('W', 'B', 'c1'));
        $this->addPiece(new Piece('W', 'B', 'f1'));
        $this->addPiece(new Piece('B', 'B', 'c8'));
        $this->addPiece(new Piece('B', 'B', 'f8'));
        
        // Queens
        $this->addPiece(new Piece('W', 'Q', 'd1'));
        $this->addPiece(new Piece('B', 'Q', 'd8'));
        
        // Kings
        $this->addPiece(new Piece('W', 'K', 'e1'));
        $this->addPiece(new Piece('B', 'K', 'e8'));

        $this->dumpState();
    }
    
    private function addPiece(Piece $piece) {
        $this->pieces[$piece->color][$piece->type][] = $piece;
    }
    
    private function getPieceAt($position, $color='') {
        foreach($this->pieces as $piecesCol) {
            foreach($piecesCol as $pieces) {
                foreach($pieces as $idx => $piece) {
                    if($piece->position == $position && (empty($color) || $piece->color == $color)) {
                        return [ $piece, $idx ];
                    }
                }
            }
        }
        return [null, null];
    }
    
    public function dumpState() {
        for($row=1; $row<=8; $row++) {
            for($col='a'; $col<='h'; $col++) {
                $piece = $this->getPieceAt($col.$row)[0];
                if(!$piece) {
                    $char = '#';
                } else if($piece->color == 'B') {
                    $char = strtolower($piece->type);
                } else {
                    $char = $piece->type;
                }
                echo $char;
            }
            echo "\n";
        }
    }
    
    public function move($move) {
        if(strstr($move, '.')) {
            // White move
            $this->__move('W', explode('.', $move)[1]);
        } else {
            // Black move
            $this->__move('B', $move);
        }
    }
    
    private function __move($color, $move) {
        if(substr($move,-1) == '+') $move = substr($move,0,-1); // Remove 'check's
        if(substr($move,-1) == '#') $move = substr($move,0,-1); // Remove 'mate's
        
        if($move == 'O-O') {
            echo "$color move: Kingside castle\n";
            $this->pieces[$color]['K'][0]->position = 'g'.($color=='W'?1:8);
            $this->pieces[$color]['R'][1]->position = 'f'.($color=='W'?1:8);
        } else if($move == 'O-O-O') {
            echo "$color move: Queenside castle\n";
            $this->pieces[$color]['K'][0]->position = 'c'.($color=='W'?1:8);
            $this->pieces[$color]['R'][0]->position = 'd'.($color=='W'?1:8);
        } else {
            $take = !!strstr($move, 'x'); // Was a take?
            if($take) $move = str_replace('x', '', $move);
            
            $target = substr($move, -2);
            $type   = substr($move, 0, -2);
            if(empty($type)) $type = 'P';
            
            if($take) {
                // Remove opponents piece
                $oColor = ($color=='W') ? 'B' : 'W';
                list($piece,$idx) = $this->getPieceAt($target, $oColor);
                
                if(!$piece) {
                    throw new Exception('Unable to find piece on target of taking');
                }
                
                unset($this->pieces[$oColor][$piece->type][$idx]);
                echo "$color take: takes $oColor $piece->type [$idx]\n";
            }
            
            echo "$color move: $type to $target\n";
            $this->pieces[$color][$type][0]->position = 'c'.($color=='W'?1:8);
        }
        
        if($color == 'B') echo "\n";
    }
}
