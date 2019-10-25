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

if($doImport) {
    $conn = pg_connect("host=vm dbname=chess user=sa_chess password=1234");
    if(!$conn) {
        echo "Error connecting to BD\n\n";
        exit(2);
    }
}

// Load PGN
$pgn = new PGN($argv[1]);

// Parse PGN
$matches = $pgn->loadMatches();
echo ($doImport ? "Importing " : 'Checking ').count($matches)." matches\n\n";

// Importing
$numMatch = 0;
$numError = 0;
while(($match = array_shift($matches))) {
    $numMatch++;
    
    try {
        $match->play(($debug === true || $numMatch == $debug));
    } catch(Exception $e) {
        $numError++;
        echo "-- !!! Skiping Match $numMatch :: ".$e->getMessage()." :: !!!\n";
        if($numMatch == $debug) die();
        continue;
    }
    
    if($doImport) {
        // Check if is duplicate
        if($match->existsOnDB($conn)) {
            echo "!!! Skiping Match $numMatch :: Already one like this on DB :: !!!\n";
            continue;
        }
        
        echo "> Importing Match $numMatch :: ".$match->getTotalMoves()." moves: ";
        pg_query($conn, 'BEGIN');
        
        $sqlMatch = $match->getInsertSQL();
        if($debug === true) echo "SQLmatch: $sqlMatch\n";
        $res = pg_query($conn, $sqlMatch . ' RETURNING id');
        if(!$res) {
            echo "!!! Skiping Match $numMatch :: DB failure :: !!!\n";
            echo "Match:\n$match\n";
            pg_query($conn, 'ROLLBACK');
            continue;
        }
        $idMatchBD = pg_fetch_array($res)[0];
        
        foreach($match->getStates() as $state) {
            $sqlState = $state->getInsertSQL($idMatchBD);
            if($debug === true) echo "SQLstate: $sqlState\n";
            $res = pg_query($conn, $sqlState);
            if(!$res) {
                echo "!!! Skiping Match $numMatch :: DB failure 2 :: !!!\n";
                pg_query($conn, 'ROLLBACK');
                continue 2;
            }
        }
        
        echo "OK!\n";
        pg_query($conn, 'COMMIT');
    }
}

if($numError) {
    echo "\nFound $numError macthes with errors!\n\n";
} else {
    echo "\nAll matches OK!\n\n";
}

exit(0);
