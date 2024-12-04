<?php

if ($argc < 3) {
    echo "Usage: php four.php <filename> <mode>\n";
    exit(1);
}

// Get the file path from the command-line argument
$filename = $argv[1];


//find xmas(1) or x-mas(2)
$mode = $argv[2];


// Check if the file exists
if (!file_exists($filename)) {
    echo "Error: The file '$filename' does not exist.\n";
    exit(1);
}

// Try to open the file
$fileContents = file_get_contents($filename);

if ($fileContents === false) {
    echo "Error: Could not read the file '$filename'.\n";
    exit(1);
}

function searchForXasMAS($handle) {
    $chars = ['A', 'M', 'S'];
    $searchGrid = loadSearchGrid($handle);
    $anchorCoords = searchGridForAnchors($searchGrid, $chars); //potential word starting points [y, x]
    $wordCount = array_reduce($anchorCoords, function($count, $coords) use($chars, $searchGrid) {
        return findXFromAnchor($searchGrid, $coords, $chars) ? $count + 1 : $count;
    }, 0);

    return $wordCount;

}

// Function to count the number of safe reports
function searchForXMAS($handle) {
    $chars = ['X', 'M', 'A', 'S'];

    $searchGrid = loadSearchGrid($handle);
    $anchorCoords = searchGridForAnchors($searchGrid, $chars); //potential word starting points [y, x]
    $wordCount = array_reduce($anchorCoords, function($count, $coords) use($chars, $searchGrid) {
        return $count + findWordsFromAnchor($searchGrid, $coords, $chars);
    }, 0);

    return $wordCount;
}

function loadSearchGrid($handle) {
    $searchGrid = [];

    // Check each report
    while (($line = fgets($handle)) !== false) {
        array_push($searchGrid, str_split($line));
    }

    return $searchGrid;
}

function searchGridForAnchors($searchGrid, $chars) {
    $anchorCoords = [];
    foreach($searchGrid as $y => $searchRow) {
        foreach($searchRow as $x => $val) {
            if($val == $chars[0]) {
                array_push($anchorCoords, [$y, $x]);
            }
        }
    }
    return $anchorCoords;
}

function findWordsFromAnchor($searchGrid, $coord, $chars) {
    $directionOptions = [[-1, -1], [-1,0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1,1]];
    
    $words = 0;
    foreach($directionOptions as $directionOption) {
        if(findWord($searchGrid, $coord, $directionOption, $chars, 1)){ $words++; }
    }
    return $words;
    
}

function findXFromAnchor($searchGrid, $coord, $chars) {
    $directionOptions = [[-1, -1], [-1, 1], [1, -1], [1,1]];
    $surroundingChars = getSurroundingChars($searchGrid, $coord, $directionOptions);
    return validateSurroundingChars($surroundingChars);
    
}

function validateSurroundingChars($surroundingChars) {
    if(!$surroundingChars) {
        return false;
    }
    $validChars = [['M', 'S'], ['M', 'S'],['M', 'S'],['M', 'S']]; //maps to $directionOptions = [[-1, -1], [-1, 1], [1, -1], [1,1]];
    foreach($surroundingChars as $key => $surroundingChar) {
        if(!in_array($surroundingChar, $validChars[$key])) {
            return false;
        }

        $opposingKey = abs($key - (count($validChars) - 1));

        $validChars[$opposingKey] = array_filter($validChars[$opposingKey], function($item) use ($surroundingChar){
            return $item !== $surroundingChar;
        });

    }
    return true;
}

function getSurroundingChars($searchGrid, $coord, $directionOptions) {
    foreach($directionOptions as $key => $directionOption) {
        $nextY = $coord[0] + $directionOption[0];
        $nextX = $coord[1] + $directionOption[1];
        if(!isset($searchGrid[$nextY][$nextX])) {
            return false;
        }
        $surroundingChars[$key] = $searchGrid[$nextY][$nextX];
    }
    return $surroundingChars;
}

function findWord($searchGrid, $coord, $directionOption, $chars, $wordIdx) {
    $nextY = $coord[0] + $directionOption[0];
    $nextX = $coord[1] + $directionOption[1];
    if(!isset($searchGrid[$nextY][$nextX])) {
        return false;
    }

    if($chars[$wordIdx] !== $searchGrid[$nextY][$nextX]) {
        return false;
    }
    $wordIdx++;

    if($wordIdx === count($chars)) {
        return true;
    }

    return findWord($searchGrid, [$nextY, $nextX], $directionOption, $chars, $wordIdx);

}


if (($handle = fopen($filename, "r")) !== false) { 
    if($mode == 1) echo 'XMAS: ' . searchForXMAS($handle);
    if($mode == 2) echo 'X-MAS: ' . searchForXasMAS($handle);
} else {
    echo "Error: Unable to open the file '$filename'.\n";
    exit(1);
}


?>
