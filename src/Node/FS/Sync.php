<?php

$readFileImpl = function($file, $opts) {
    $res = @file_get_contents($file);
    if ($res === false) {
        throw new \Exception("Failed to read file: $file");
    }
    return $res;
};

$writeFileImpl = function($file, $buff, $opts) {
    $res = @\file_put_contents($file, $buff);
    if ($res === false) {
        throw new \Exception("Failed to write file: $file");
    }
};

$mkdirImpl = function($file, $opts) {
    $res = @mkdir($file, 0777, true);
    if (!$res && !is_dir($file)) {
        throw new \Exception("Failed to create directory: $file");
    }
};

$readdirImpl = function($file) {
    $res = @scandir($file);
    if ($res === false) {
        throw new \Exception("Failed to read directory: $file");
    }
    return array_values(array_filter($res, function($item) {
        return $item !== '.' && $item !== '..';
    }));
};

$renameImpl = function($old, $new) {
    $res = @rename($old, $new);
    if ($res === false) {
        throw new \Exception("Failed to rename $old to $new");
    }
};

$unlinkImpl = function($file) {
    $res = @unlink($file);
    if ($res === false && file_exists($file)) {
        throw new \Exception("Failed to unlink: $file");
    }
};

$appendFileSyncImpl = function($file, $buff, $opts) {
    $res = @\file_put_contents($file, $buff, FILE_APPEND);
    if ($res === false) {
        throw new \Exception("Failed to append to file: $file");
    }
};

$exports['readFileImpl'] = $readFileImpl;
$exports['writeFileImpl'] = $writeFileImpl;
$exports['mkdirImpl'] = $mkdirImpl;
$exports['readdirImpl'] = $readdirImpl;
$exports['renameImpl'] = $renameImpl;
$exports['unlinkImpl'] = $unlinkImpl;
$exports['appendFileSyncImpl'] = $appendFileSyncImpl;
return $exports;
