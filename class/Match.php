<?php
class Match {
    private $headersToSave = ['event', 'date', 'site', 'white', 'black', 'result', 'whiteelo', 'blackelo'];
    private $event, $date, $site;
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
        
        $this->whiteelo = (int)$this->whiteelo;
        $this->blackelo = (int)$this->blackelo;
        
        $this->moves  = trim($moves);
        $this->states = [];
    }
    
    public function play($debug=false) {
        if($debug) echo "moves: $this->moves\n\n";
        
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
            
            $state = new State($numMove, $move, $board);
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
        $sql  = "INSERT INTO match(totmoves,moves,".implode(',', $this->headersToSave).") VALUES (".$this->getTotalMoves().", '$this->moves' ,'";
        $vals = [];
        foreach($this->headersToSave as $atrName) {
            $vals[] = str_replace("'", "''", utf8_encode($this->$atrName)); // To UTF and Escape the "'"
        }
        return $sql . implode("', '", $vals) . "')";
    }

    /**
     * Check if this Match is already on DB
     * @param resource $conn
     * @return integer ID of dup match
     */
    public function existsOnDB($conn) {
        $eventBD = str_replace("'", "''", $this->event);
        $whiteBD = str_replace("'", "''", $this->white);
        $blackBD = str_replace("'", "''", $this->black);
        $res = pg_query($conn, "SELECT id FROM match WHERE event='$eventBD' AND white='$whiteBD' AND black='$blackBD' AND moves='$this->moves'");
        return pg_fetch_array($res)[0];
    }
    
    /**
     * check if the needed tables exists on the DB
     * @param resource $conn
     * @return boolean had to create tables?
     */
    static function checkDB($conn) {
        $res = pg_query($conn, "SELECT * FROM information_schema.tables WHERE table_name = 'match'");
        if(pg_num_rows($res) != 1) {
            // CREATE TABLE match
            pg_query($conn, "
CREATE TABLE match (
	id       serial PRIMARY KEY,
	moves    text,
	totmoves integer,
	event    text,
	date     text,
	site     text,
	white    text,
	black    text,
	result   integer, -- 0=tie, 1=white, 2=black, 3=others
	whiteelo smallint,
	blackelo smallint
);
CREATE INDEX idx_match_event ON match (event);
CREATE INDEX idx_match_white ON match (white);
CREATE INDEX idx_match_black ON match (black);
CREATE INDEX idx_match_moves ON match (moves);

CREATE TABLE boardstate (
	idmatch  integer REFERENCES match(id),
	nummove  smallint, -- seq: 11,12,21,22,31,32,...
	origmove text,
	state    text
);
CREATE INDEX idx_state ON boardstate (state);
");
            return true;
        }
        
        return false;
    }
    
    public function __toString() {
        return "[    event: $this->event ]\n".
               "[     date: $this->date ]\n".
               "[     site: $this->site ]\n".
               "[    white: $this->white ]\n".
               "[    black: $this->black ]\n".
               "[   result: $this->result ]\n".
               "[ whiteelo: $this->whiteelo ]\n".
               "[ blackelo: $this->blackelo ]\n";
    }
}
