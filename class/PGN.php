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
        $pastLin = '';
        while($lin = fgets($fp)) {
            if($lin == "\n" || $lin == "\r\n") {
                // blank line
                if($pastLin == "\n" || $pastLin == "\r\n") {
                    // Ignore duplicatet blanks
                    continue;
                }
                $state = 1 - $state;
                if(!$state) {
                    $matches[] = new Match($heads, $moves);
                    
                    $moves = '';
                    $heads = [];
                }
            } else if(!$state) {
                // header
                if(!strstr($lin, '"')) {
                    throw new Exception("Invalid header [$lin]");
                }
                list($ch,$vl) = explode('"', $lin);
                $heads[substr(strtolower($ch), 1, -1)] = $vl;
            } else {
                // match
                $moves .= str_replace(["\r", "\n"], '', $lin) . ' ';
            }
            $pastLin = $lin;
        }
        
        return $matches;
    }
}
