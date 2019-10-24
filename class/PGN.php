<?php
class PGN {
    private $fileName;
    
    public function __construct($fileName) {
        if(!file_exists($fileName)) {
            throw new Exception('File not found');
        }
        
        $this->fileName = $fileName;
    }
    
    /**
     * Parse $fileName to return a list of Match objects
     * @throws Exception
     * @return Match[]
     */
    public function loadMatches() {
        $matches = [];
        
        $fp = fopen($this->fileName, 'r');
        if(!$fp) {
            throw new Exception("Unable to read file");
        }
        
        $state = 0;
        $heads = [];
        $moves = '';

        // Parse the file
        while($lin = fgets($fp)) {
            if($lin == "\n" || $lin == "\r\n") {
                // blank line
                $state = 1 - $state;
                if(!$state) {
                    $matches[] = new Match($heads, $moves);
                    
                    $moves = '';
                    $heads = [];
                }
            } else if(!$state) {
                // header
                list($ch,$vl) = explode('"', $lin);
                $heads[substr(strtolower($ch), 1, -1)] = $vl;
            } else {
                // match
                $moves .= str_replace(["\r", "\n"], '', $lin) . ' ';
            }
        }
        
        return $matches;
    }
}
