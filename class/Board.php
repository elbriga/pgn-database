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
    }
    
    private function addPiece(Piece $piece) {
        if(!isset($this->pieces[$piece->color][$piece->type]) || empty($this->pieces[$piece->color][$piece->type])) {
            $piece->index = 0;
        } else {
            $ks = array_keys($this->pieces[$piece->color][$piece->type]);
            $piece->index = array_pop($ks) + 1;
        }
        
        $this->pieces[$piece->color][$piece->type][$piece->index] = $piece;
    }
    
    public function removePiece(Piece $piece) {
        unset($this->pieces[$piece->color][$piece->type][$piece->index]);
    }
    
    private function movePiece(Piece $piece, $target) {
        $this->pieces[$piece->color][$piece->type][$piece->index]->position = $target;
    }
    
    public function getPieceAt($position, $color=null) {
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
    
    private function getPieceToMove($color, $type, $targetPosition, $taking, $hint, $debug=false) {
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
        if($tot == 1)  {
            return $pieceAble[0];
        } else if(!$tot) {
            throw new Exception('Unable to find piece to move 1');
        }
        
        
        
        if($debug) echo "\n--*******************>>> $tot pieces can move [h:$hint]!\n";

        // check for pieces on the way
        $canMove = [];
        foreach($pieceAble as $piece) {
            if($debug) echo "--*******************>>> $piece->type at $piece->position [d:$piece->distanceMoved]\n";
            
            if(empty($hint)) {
                // Choose wich seeing if there are other pieces on the way
                if($piece->canMoveThrought($targetPosition, $this)) {
                    $canMove[] = $piece;
                }
            } else if(strstr($piece->position, $hint)) {
                $canMove[] = $piece;
            } else {
                //throw new Exception('???? PGN invalido?');
            }
        }
        
        $totCanMovePieces = count($canMove);
        if($totCanMovePieces === 1) {
            $tiePiece = $canMove[0];
            if($debug) echo "--***** CHOOSE ******>>> $tiePiece->type at $tiePiece->position\n";
            return $tiePiece;
        } else if(!$totCanMovePieces) {
            throw new Exception('Unable to find piece to move 2');
        }
        
        
        
        // check for pinned pieces
        $notPinned = [];
        foreach($canMove as $piece) {
            if($debug) echo "--*******************>>> $piece->type at $piece->position is pinned? ";
            
            if(!$piece->isPinned($this)) {
                if($debug) echo "No!\n";
                $notPinned[] = $piece;
            } else {
                if($debug) echo "YES!\n";
            }
        }
        
        $totNotPinnedPieces = count($notPinned);
        if($totNotPinnedPieces === 1) {
            $tiePiece = $notPinned[0];
            if($debug) echo "--***** CHOOSE ******>>> $tiePiece->type at $tiePiece->position unpinned\n";
            return $tiePiece;
        } else if(!$totNotPinnedPieces) {
            throw new Exception('Unable to find piece to move 3');
        }
        
        throw new Exception($totNotPinnedPieces.' pieces can move!', 10);
    }
    
    public function move($move, $debug) {
        if(strstr($move, '.')) {
            // White move
            $this->__move('W', explode('.', $move)[1], $debug);
        } else {
            // Black move
            $this->__move('B', $move, $debug);
        }
    }
    
    private function __move($color, $move, $debug) {
        $origMove = $move;
        
        // Remove possible old notations
        $move = str_replace('e.p.', '', $move); // en passant
        
        $check = (substr($move,-1) == '+') ? ' CHECK'      : '';
        $mate  = (substr($move,-1) == '#') ? ' CHECKMATE!' : '';
        if($check || $mate) $move = substr($move,0,-1); // Remove 'check's
        
        // promotions
        $promote = '';
        if(substr($move, -2, 1) == '=') {
            $promote = substr($move,-1);
            $move = substr($move,0,-2); // Remove '=X'
        }
                
        if($move == 'O-O') {
            if($debug) echo "[$origMove] $color move: Kingside castling\n";
            $this->movePiece($this->pieces[$color]['K'][0], 'g'.($color=='W'?1:8));
            $this->movePiece($this->pieces[$color]['R'][1], 'f'.($color=='W'?1:8));
        } else if($move == 'O-O-O') {
            if($debug) echo "[$origMove] $color move: Queenside castling\n";
            $this->movePiece($this->pieces[$color]['K'][0], 'c'.($color=='W'?1:8));
            $this->movePiece($this->pieces[$color]['R'][0], 'd'.($color=='W'?1:8));
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
                } else if($lMove == 5) {
                    $from = $move[1].$move[2];
                }
            }
            
            if($debug) echo "[$origMove] $color move: $type ";
            
            $pieceMove = $this->getPieceToMove($color, $type, $target, $take, $from, $debug);
            
            if($debug) echo "from $pieceMove->position to $target";
            $this->movePiece($pieceMove, $target);
            if($promote) {
                if($debug) echo " promoting to $promote";
                $this->removePiece($pieceMove);
                
                $pieceMove->type = $promote;
                $this->addPiece($pieceMove);
            }
            
            if($take) {
                // Remove opponents piece
                $oColor = ($color=='W') ? 'B' : 'W';
                if($debug) echo " taking $oColor ";
                
                $enPassant = '';
                $pieceTake = $this->getPieceAt($target, $oColor);
                if(!$pieceTake) {
                    // Check for en passant
                    if($pieceMove->type == 'P') {
                        if($pieceMove->color == 'W' && $pieceMove->position[1] == '6') {
                            $pieceTake = $this->getPieceAt($target[0].'5', $oColor);
                        } else if($pieceMove->color == 'B' && $pieceMove->position[1] == '3') {
                            $pieceTake = $this->getPieceAt($target[0].'4', $oColor);
                        }
                        $enPassant = ' en passant';
                    }
                    
                    if(!$pieceTake) {
                        throw new Exception('Unable to find piece on target of taking');
                    }
                }
                
                $this->removePiece($pieceTake);
                if($debug) echo "$pieceTake->type [i:$pieceTake->index]$enPassant";
            }
            if($debug) echo "$check$mate\n";
        }
    }
    
    /**
     * Calculate how many pieces are checking the $color King
     * @param string $color
     */
    public function totalChecks($color) {
        $total = 0;
        $king  = $this->pieces[$color]['K'][0];
        
        $otherColor = ($color == 'W') ? 'B' : 'W';
        foreach($this->pieces[$otherColor] as $pieces) {
            foreach($pieces as $piece) {
                if($piece->isCheking($king, $this)) {
                    $total++;
                }
            }
        }
        
        return $total;
    }
}
