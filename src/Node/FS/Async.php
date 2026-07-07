<?php

$readFileImpl = function($file, $opts, $cb) {
    $res = @file_get_contents($file);
    if ($res === false) {
        $err = new \Exception("Failed to read file: $file");
        $cb($err, null);
    } else {
        $cb(null, $res);
    }
};

$writeFileImpl = function($file, $buff, $opts, $cb) {
    $res = file_put_contents($file, $buff);
    if ($res === false) {
        $errArr = error_get_last();
        $msg = "Failed to write file: $file. Error: " . ($errArr ? $errArr['message'] : 'Unknown');
        file_put_contents('php://stderr', $msg . "\n");
        $err = new \Exception($msg);
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$mkdirImpl = function($file, $opts, $cb) {
    $res = @mkdir($file, 0777, true);
    if (!$res && !is_dir($file)) {
        $err = new \Exception("Failed to create directory: $file");
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$readdirImpl = function($file, $cb) {
    $res = @scandir($file);
    if ($res === false) {
        $err = new \Exception("Failed to read directory: $file");
        $cb($err, null);
    } else {
        $filtered = array_values(array_filter($res, function($item) {
            return $item !== '.' && $item !== '..';
        }));
        $cb(null, $filtered);
    }
};

$renameImpl = function($old, $new, $cb) {
    $res = @rename($old, $new);
    if ($res === false) {
        $err = new \Exception("Failed to rename $old to $new");
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$unlinkImpl = function($file, $cb) {
    $res = @unlink($file);
    if ($res === false && file_exists($file)) {
        $err = new \Exception("Failed to unlink: $file");
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$exports['readFileImpl'] = $readFileImpl;
$exports['writeFileImpl'] = $writeFileImpl;
$exports['mkdirImpl'] = $mkdirImpl;
$exports['readdirImpl'] = $readdirImpl;
$exports['renameImpl'] = $renameImpl;
$exports['unlinkImpl'] = $unlinkImpl;
return $exports;
