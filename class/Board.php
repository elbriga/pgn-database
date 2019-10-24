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
        $piece->index = @count($this->pieces[$piece->color][$piece->type]);
        
        $this->pieces[$piece->color][$piece->type][$piece->index] = $piece;
    }
    
    private function getPieceAt($position, $color=null) {
        if($color) {
            foreach($this->pieces[$color] as $pieces) {
                foreach($pieces as $piece) {
                    if($piece->position == $position) {
                        return $piece;
                    }
                }
            }
            return null;
        }
        
        foreach($this->pieces as $piecesCol) {
            foreach($piecesCol as $pieces) {
                foreach($pieces as $piece) {
                    if($piece->position == $position) {
                        return $piece;
                    }
                }
            }
        }
        return null;
    }
    
    private function getPieceToMove($color, $type, $targetPosition, $taking, $hint) {
        if(count($this->pieces[$color][$type]) === 1) {
            // there is only one!
            return $this->pieces[$color][$type][  array_keys($this->pieces[$color][$type])[0]  ];
        }
        
        $pieceAble = [];
        foreach($this->pieces[$color][$type] as $piece) {
            if($piece->canMoveTo($targetPosition, $taking) > 0) {
                // Check if this piece can move to target
                $pieceAble[] = $piece;
            }
        }
        
        $tot = count($pieceAble);
        if($tot == 1)  return $pieceAble[0];
        else if(!$tot) return null;
        else {
            echo "--*******************>>> $tot pieces can move [$hint]!\n";
            $closer = 10;
            $tiePiece = null;
            foreach($pieceAble as $piece) {
                echo "--*******************>>> $piece->type at $piece->position [$piece->distanceMoved]\n";
                
                if(empty($hint)) {
                    // chose wich based on distance! rsrsrsrs
                    if($piece->distanceMoved < $closer) {
                        $tiePiece = $piece;
                        $closer   = $piece->distanceMoved;
                    }
                } else if(strstr($piece->position, $hint)) {
                    $tiePiece = $piece;
                } else {
                    // ???? PGN invalido?
                }
            }
            
            echo "--***** CHOOSE ******>>> $tiePiece->type at $tiePiece->position\n";
            return $tiePiece;
        }
    }
    
    public function dumpState() {
        for($row=8; $row>0; $row--) {
            for($col='a'; $col<='h'; $col++) {
                $piece = $this->getPieceAt($col.$row);
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
        $origMove = $move;
        
        if(substr($move,-1) == '+') $move = substr($move,0,-1); // Remove 'check's
        if(substr($move,-1) == '#') $move = substr($move,0,-1); // Remove 'mate's
        
        if($move == 'O-O') {
            echo "[$origMove] $color move: Kingside castle\n";
            $this->pieces[$color]['K'][0]->position = 'g'.($color=='W'?1:8);
            $this->pieces[$color]['R'][1]->position = 'f'.($color=='W'?1:8);
        } else if($move == 'O-O-O') {
            echo "[$origMove] $color move: Queenside castle\n";
            $this->pieces[$color]['K'][0]->position = 'c'.($color=='W'?1:8);
            $this->pieces[$color]['R'][0]->position = 'd'.($color=='W'?1:8);
        } else {
            $take = !!strstr($move, 'x'); // Was a take?
            if($take) $move = str_replace('x', '', $move);
            
            $from  = '';
            $lMove = strlen($move);
            
            $target = substr($move, -2);
            if($lMove == 2) $type = 'P';
            else {
                $type = $move[0];
                if($type >= 'a' && $type <= 'h') {
                    $from = $type;
                    $type = 'P';
                } else if($lMove == 4) {
                    $from = $move[1];
                }
            }
            
            echo "[$origMove] $color move: $type to $target ";
            if($take) {
                // Remove opponents piece
                $oColor = ($color=='W') ? 'B' : 'W';
                echo "taking $oColor ";
                
                $piece = $this->getPieceAt($target, $oColor);
                if(!$piece) {
                    throw new Exception('Unable to find piece on target of taking');
                }
                
                unset($this->pieces[$oColor][$piece->type][$piece->index]);
                echo "$piece->type [$piece->index]";
            }
            echo "\n";
            
            $piece = $this->getPieceToMove($color, $type, $target, $take, $from);
            if(!$piece) {
                throw new Exception('Unable to find piece to move');
            }
            
            $this->pieces[$color][$type][$piece->index]->position = $target;
        }
        
        //if($color == 'B') echo "\n";
    }
}
