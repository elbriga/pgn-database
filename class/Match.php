<?php
class Match {
    private $headersToSave = ['event', 'site', 'white', 'black', 'result', 'whiteelo', 'blackelo'];
    private $event, $site;
    private $white, $black, $result;
    private $whiteelo, $blackelo;
    
    private $moves, $states;
    
    public function __construct($headers, $moves) {
        if(!isset($headers['result'])) {
            throw new Exception ("Match without result");
        }
        
        foreach($this->headersToSave as $atrName) {
            $this->$atrName = isset($headers[$atrName]) ? $headers[$atrName] : '';
        }
        
        if(strstr($headers['result'], '1/2')) $this->result = 0;
        else if($headers['result'] == '1-0')  $this->result = 1;
        else if($headers['result'] == '0-1')  $this->result = 2;
        else                                  $this->result = 3;
        unset($headers['result']);
        
        if(empty($this->whiteelo)) $this->whiteelo = 0;
        if(empty($this->blackelo)) $this->blackelo = 0;
        
        $this->moves  = $moves;
        $this->states = [];
    }
    
    public function play($debug=false) {
        // Proccess $moves
        $moves  = array_filter(explode(' ', $this->moves));
        $result = array_pop($moves);
        if(strstr($result, '.')) {
            throw new Exception("Match without result");
        }
        
        $board = new Board();
        $numMove = 11;
        $isWhite = 1;
        foreach($moves as $move) {
            $board->move($move, $debug);
            
            $state = new State($numMove, $move, $board->dumpState());
            if($debug) echo "$state\n";
            
            $numMove += $isWhite ? 1 : 9; // create a sequence like: 11,12,21,22,31,32,...
            $isWhite = 1 - $isWhite;
            
            $this->states[] = $state;
        }
    }
    
    public function getTotalMoves() {
        return count($this->states);
    }
    public function getStates() {
        return $this->states;
    }
    
    public function getInsertSQL() {
        $sql  = "INSERT INTO match(pgn,".implode(',', $this->headersToSave).") VALUES ('".trim($this->moves)."','";
        $vals = [];
        foreach($this->headersToSave as $atrName) {
            $vals[] = $this->$atrName;
        }
        return $sql . implode("','", $vals) . "')";
    }

    /**
     * Check if this Match is already on DB
     * @param resource $conn
     * @return boolean
     */
    public function existsOnDB($conn) {
        $res = pg_query($conn, "SELECT COUNT(id) FROM match WHERE moves = '$this->moves'");
        return (pg_fetch_array($res)[0] > 0);
    }
}
