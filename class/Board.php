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
        $piece->index = @count($this->pieces[$piece->color][$piece->type]);
        
        $this->pieces[$piece->color][$piece->type][$piece->index] = $piece;
    }
    
    private function removePiece(Piece $piece) {
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
        } else {
            if($debug) echo "\n--*******************>>> $tot pieces can move [h:$hint]!\n";

            $tiePiece = [];
            foreach($pieceAble as $piece) {
                if($debug) echo "--*******************>>> $piece->type at $piece->position [d:$piece->distanceMoved]\n";
                
                if(empty($hint)) {
                    // Choose wich seeing if there are other pieces on the way
                    if($piece->canMoveThrought($targetPosition, $this)) {
                        $tiePiece[] = $piece;
                    }
                } else if(strstr($piece->position, $hint)) {
                    $tiePiece[] = $piece;
                } else {
                    //throw new Exception('???? PGN invalido?');
                }
            }
            
            $totTiePieces = count($tiePiece);
            if($totTiePieces === 1) {
                if($debug) echo "--***** CHOOSE ******>>> $tiePiece->type at $tiePiece->position\n";
                return $tiePiece[0];
            } else if(!$totTiePieces) {
                throw new Exception('Unable to find piece to move 2');
            }
            
            // TODO :: check for pinned pieces
            throw new Exception('2 pieces can move!', 10);
            if($debug) echo "--***** CHOOSE ******>>> $tiePiece->type at $tiePiece->position\n";
        }
    }
    
    public function dumpState() {
        $state = '';
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
                $state .= $char;
            }
        }
        return $state;
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
            if($debug) echo "[$origMove] $color move: Kingside castle\n";
            $this->movePiece($this->pieces[$color]['K'][0], 'g'.($color=='W'?1:8));
            $this->movePiece($this->pieces[$color]['R'][1], 'f'.($color=='W'?1:8));
        } else if($move == 'O-O-O') {
            if($debug) echo "[$origMove] $color move: Queenside castle\n";
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
}
