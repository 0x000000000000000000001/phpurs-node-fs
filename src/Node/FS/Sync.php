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

$exports['readFileSyncImpl'] = $readFileImpl;
$exports['writeFileSyncImpl'] = $writeFileImpl;
$exports['mkdirSyncImpl'] = $mkdirImpl;
$exports['readdirSyncImpl'] = $readdirImpl;
$exports['renameSyncImpl'] = $renameImpl;
$exports['unlinkSyncImpl'] = $unlinkImpl;
$exports['appendFileSyncImpl'] = $appendFileSyncImpl;

$exports['accessImpl'] = function(...$args) { throw new \Exception("Function accessImpl is not implemented yet. PRs welcome!"); };
$exports['copyFileImpl'] = function(...$args) { throw new \Exception("Function copyFileImpl is not implemented yet. PRs welcome!"); };
$exports['mkdtempImpl'] = function(...$args) { throw new \Exception("Function mkdtempImpl is not implemented yet. PRs welcome!"); };
$exports['truncateSyncImpl'] = function(...$args) { throw new \Exception("Function truncateSyncImpl is not implemented yet. PRs welcome!"); };
$exports['chownSyncImpl'] = function(...$args) { throw new \Exception("Function chownSyncImpl is not implemented yet. PRs welcome!"); };
$exports['chmodSyncImpl'] = function(...$args) { throw new \Exception("Function chmodSyncImpl is not implemented yet. PRs welcome!"); };
$exports['statSyncImpl'] = function(...$args) { throw new \Exception("Function statSyncImpl is not implemented yet. PRs welcome!"); };
$exports['lstatSyncImpl'] = function(...$args) { throw new \Exception("Function lstatSyncImpl is not implemented yet. PRs welcome!"); };
$exports['linkSyncImpl'] = function(...$args) { throw new \Exception("Function linkSyncImpl is not implemented yet. PRs welcome!"); };
$exports['symlinkSyncImpl'] = function(...$args) { throw new \Exception("Function symlinkSyncImpl is not implemented yet. PRs welcome!"); };
$exports['readlinkSyncImpl'] = function(...$args) { throw new \Exception("Function readlinkSyncImpl is not implemented yet. PRs welcome!"); };
$exports['realpathSyncImpl'] = function(...$args) { throw new \Exception("Function realpathSyncImpl is not implemented yet. PRs welcome!"); };
$exports['rmdirSyncImpl'] = function(...$args) { throw new \Exception("Function rmdirSyncImpl is not implemented yet. PRs welcome!"); };
$exports['rmSyncImpl'] = function(...$args) { throw new \Exception("Function rmSyncImpl is not implemented yet. PRs welcome!"); };
$exports['utimesSyncImpl'] = function(...$args) { throw new \Exception("Function utimesSyncImpl is not implemented yet. PRs welcome!"); };
$exports['existsSyncImpl'] = function(...$args) { throw new \Exception("Function existsSyncImpl is not implemented yet. PRs welcome!"); };
$exports['openSyncImpl'] = function(...$args) { throw new \Exception("Function openSyncImpl is not implemented yet. PRs welcome!"); };
$exports['readSyncImpl'] = function(...$args) { throw new \Exception("Function readSyncImpl is not implemented yet. PRs welcome!"); };
$exports['writeSyncImpl'] = function(...$args) { throw new \Exception("Function writeSyncImpl is not implemented yet. PRs welcome!"); };
$exports['fsyncSyncImpl'] = function(...$args) { throw new \Exception("Function fsyncSyncImpl is not implemented yet. PRs welcome!"); };
$exports['closeSyncImpl'] = function(...$args) { throw new \Exception("Function closeSyncImpl is not implemented yet. PRs welcome!"); };

return $exports;
