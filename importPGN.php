<?php
include('class/State.php');
include('class/Match.php');
include('class/Piece.php');
include('class/Board.php');
include('class/PGN.php');

// Command line
$argv = $GLOBALS['argv'];
if(!isset($argv[1]) || empty($argv[1]) || !file_exists($argv[1])) {
    echo "Use: importPGN <File.pgn>\n\n";
    exit(1);
}

$debug    = !isset($argv[2]) ? 0 : ($argv[2]=='BD' ? true : $argv[2]+0);
$doImport = (isset($argv[3]) && $argv[3]=='IMP');

$conn = pg_connect("host=vm dbname=chess user=sa_chess password=1234");
if(!$conn) {
    echo "Error connecting to BD\n\n";
    exit(2);
}

// Load PGN
$pgn = new PGN($argv[1]);

// Parse PGN
$matches = $pgn->loadMatches();
echo "Importing ".count($matches)." matches\n\n";

// Importing
$numMatch = 0;
while(($match = array_shift($matches))) {
    $numMatch++;
    
    try {
        $match->play(($debug === true || $numMatch == $debug));
    } catch(Exception $e) {
        echo "-- !!! Skiping Match $numMatch :: ".$e->getMessage()." :: !!!\n";
        continue;
    }
    
    if($doImport) {
        // Check if is duplicate
        if($match->existsOnDB($conn)) {
            echo "-- !!! Skiping Match $numMatch :: Already one like this on DB :: !!!\n";
            continue;
        }
        
        echo "-- Importing Match $numMatch :: ".$match->getTotalMoves()." moves: ";
        pg_query($conn, 'BEGIN');
        
        $sqlMatch = $match->getInsertSQL();
        if($debug == 2) echo "SQLmatch: $sqlMatch\n";
        $res = pg_query($conn, $sqlMatch . ' RETURNING id');
        if(!$res) {
            echo "-- !!! Skiping Match $numMatch :: DB failure :: !!!\n";
            pg_query($conn, 'ROLLBACK');
            continue;
        }
        $idMatchBD = pg_fetch_array($res)[0];
        
        foreach($match->getStates() as $state) {
            $sqlState = $state->getInsertSQL($idMatchBD);
            if($debug == 2) echo "SQLstate: $sqlState\n";
            $res = pg_query($conn, $sqlState);
            if(!$res) {
                echo "-- !!! Skiping Match $numMatch :: DB failure 2 :: !!!\n";
                pg_query($conn, 'ROLLBACK');
                continue 2;
            }
        }
        
        echo "OK!\n";
        pg_query($conn, 'COMMIT');
    }
}

exit(0);
