<?php
include('class/Piece.php');
include('class/Board.php');

if(!isset($argv[1]) || empty($argv[1]) || !file_exists($argv[1])) {
    echo "Use: importPGN <File.pgn>\n\n";
    exit(1);
}

$fp = fopen($argv[1], 'r');
if(!$fp) {
    echo "File $argv[1] not found!\n\n";
    exit(2);
}

$headersToSave    = ['event', 'site', 'white', 'black', 'result', 'whiteelo', 'blackelo'];
$strHeadersToSave = implode(',', $headersToSave);

$state = 0;
$heads = [];
$match = '';
while($lin = fgets($fp)) {
    if($lin == "\n" || $lin == "\r\n") {
        // blank line
        $state = 1 - $state;
        if(!$state) {
            // Save match
            if(!isset($heads['result'])) {
                echo "-- Match withou result!\n";
                $match = '';
                $heads = [];
                continue;
            }
            
            if(strstr($heads['result'], '1/2')) $heads['result'] = 0;
            else if($heads['result'] == '1-0')  $heads['result'] = 1;
            else if($heads['result'] == '0-1')  $heads['result'] = 2;
            else                                $heads['result'] = 3;
            
            $sql  = "INSERT INTO match(pgn,$strHeadersToSave) VALUES ('".substr($match,0,-1)."','";
            $vals = [];
            foreach($headersToSave as $head) {
                $vals[] = isset($heads[$head]) ? $heads[$head] : '';
            }
            $sql .= implode("','", $vals) . "') RETURNING id";
            
            echo "$sql\n";
            
            // Proccess PGN
            $moves  = array_filter(explode(' ', $match));
            $result = array_pop($moves);
            if(strstr($result, '.')) {
                echo "-- Match withou result!\n";
                $match = '';
                $heads = [];
                continue;
            }
            
            $board = new Board();
            foreach($moves as $move) {
                $board->move($move);
            }
die();
            
            $match = '';
            $heads = [];
        }
    } else if(!$state) {
        // header
        list($ch,$vl) = explode('"', $lin);
        $heads[substr(strtolower($ch), 1, -1)] = $vl;
    } else {
        // match
        $match .= str_replace(["\r", "\n"], '', $lin) . ' ';
    }
}
