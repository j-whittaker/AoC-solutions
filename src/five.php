<?php

if ($argc < 3) {
    echo "Usage: php five.php <filename> <mode>\n";
    exit(1);
}

// Get the file path from the command-line argument
$filename = $argv[1];


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

function parseRules($rawRulesStr) {
    return explode("\n", $rawRulesStr);
}

function parseUpdates($rawUpdatesStr) {
    $rawUpdates = explode("\n", $rawUpdatesStr);
    return array_map(function($rawUpdate) {
        return explode(',', $rawUpdate);
    }, $rawUpdates);
}

function parseRulesAndUpdates($fileContents) {
    $parts = explode("\n\n", $fileContents);
    [$rawRulesStr, $rawUpdatesStr] = [$parts[0], $parts[1]];
    return [parseRules($rawRulesStr), parseUpdates($rawUpdatesStr)];
}

function validateUpdate($update, $rules) {
    return array_reduce(array_keys($update), function ($valid, $idx) use($update, $rules) { 
        $page = $update[$idx];
        if(isset($rules[$page])) {
            return $valid && validatePage($update, $idx, $rules[$page]);
        }
        return $valid && true; 
    }, true); 
}

function validatePage($updates, $key, $rule) {
    $previousPages = array_slice($updates, 0, $key);
    return count(array_intersect($previousPages, $rule)) === 0;
}

function prepareRuleAndAddToRulesCollection($newRulesArray, $rawRule) {
    $parsedRule = explode('|', $rawRule);
    [$primary, $secondary] = [$parsedRule[0], $parsedRule[1]];
        
    if(!isset($newRulesArray[$primary])) {
        $newRulesArray[$primary] = [];
    }

    array_push($newRulesArray[$primary], $secondary);
    return $newRulesArray;
}

function prepareRulesCollection($rules) {
    return array_reduce($rules, function($newRulesArray, $rawRule) {
        return prepareRuleAndAddToRulesCollection($newRulesArray, $rawRule);
    }, []);
}

function validateUpdatesWithPreparedRules($updates, $rules, $getValidRules = true) {
    return array_filter($updates, function($update) use($rules, $getValidRules) {
        return $getValidRules ? validateUpdate($update, $rules) : !validateUpdate($update, $rules);
    });
}

function countMiddlePageNumberOfValidUpdates($validUpdates) {
    return array_reduce($validUpdates, function($sum, $validUpdate) {
        $middleIdx = floor(count($validUpdate) / 2);
        return $sum + $validUpdate[$middleIdx];
    }, 0);
}

function fixInvalidUpdate($update, $rules) {
    $sortedUpdate = $update;
    usort($sortedUpdate, function($a, $b) use ($rules) {
        return (isset($rules[$a]) && in_array($b, $rules[$a])) ? -1 : 1;
    });
    return $sortedUpdate;
}

function fixInvalidUpdatesOrder($invalidUpdates, $rules) {
    return array_map(function($update) use ($rules) {
        return fixInvalidUpdate($update, $rules);
    }, $invalidUpdates);
}

function parseInputandGetMiddlePageNumberCount($fileContents, $mode = 1) {
    [$rules, $updates] = parseRulesAndUpdates($fileContents);
    echo "Rules: " . json_encode($rules) . "\n";
    echo "Updates: " . json_encode($updates) . "\n";
    $preparedRules = prepareRulesCollection($rules);
    echo "Prepared Rules: " . json_encode($preparedRules) . "\n";
    $validUpdates = validateUpdatesWithPreparedRules($updates, $preparedRules);
    $invalidUpdates = validateUpdatesWithPreparedRules($updates, $preparedRules, false);
    $fixedUpdates = fixInvalidUpdatesOrder($invalidUpdates, $preparedRules);
    echo "Valid Updates: " . json_encode($validUpdates) . "\n";
    echo "Fixed updates: " . json_encode($fixedUpdates) . "\n";
    return $mode == 1 ? countMiddlePageNumberOfValidUpdates($validUpdates) : countMiddlePageNumberOfValidUpdates($fixedUpdates);
}

echo 'Page number count: ' . parseInputandGetMiddlePageNumberCount($fileContents, $mode);

?>