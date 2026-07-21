<?php

$readFileImpl = function($file, $opts, $cb) {
    if (function_exists('\\Amp\\File\\read') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $cb) {
            try {
                $res = \Amp\File\read($file);
                $cb(null, $res);
            } catch (\Throwable $err) {
                $cb($err, null);
            }
        });
        return;
    }
    
    $res = @file_get_contents($file);
    if ($res === false) {
        $err = new \Exception("Failed to read file: $file");
        $cb($err, null);
    } else {
        $cb(null, $res);
    }
};

$writeFileImpl = function($file, $buff, $opts, $cb) {
    if (function_exists('\\Amp\\File\\write') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $buff, $cb) {
            try {
                \Amp\File\write($file, $buff);
                $cb(null, null);
            } catch (\Throwable $err) {
                $cb($err, null);
            }
        });
        return;
    }

    $res = \file_put_contents($file, $buff);
    if ($res === false) {
        $errArr = error_get_last();
        $msg = "Failed to write file: $file. Error: " . ($errArr ? $errArr['message'] : 'Unknown');
        \file_put_contents('php://stderr', $msg . "\n");
        $err = new \Exception($msg);
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$mkdirImpl = function($file, $opts, $cb) {
    if (function_exists('\\Amp\\File\\createDirectoryRecursively') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $cb) {
            try {
                \Amp\File\createDirectoryRecursively($file, 0777);
                $cb(null, null);
            } catch (\Throwable $err) {
                if (!is_dir($file)) {
                    $cb($err, null);
                } else {
                    $cb(null, null);
                }
            }
        });
        return;
    }

    $res = @mkdir($file, 0777, true);
    if (!$res && !is_dir($file)) {
        $err = new \Exception("Failed to create directory: $file");
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$readdirImpl = function($file, $cb) {
    if (function_exists('\\Amp\\File\\listFiles') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $cb) {
            try {
                $res = \Amp\File\listFiles($file);
                $cb(null, array_values($res));
            } catch (\Throwable $err) {
                $cb($err, null);
            }
        });
        return;
    }

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
    if (function_exists('\\Amp\\File\\move') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($old, $new, $cb) {
            try {
                \Amp\File\move($old, $new);
                $cb(null, null);
            } catch (\Throwable $err) {
                $cb($err, null);
            }
        });
        return;
    }

    $res = @rename($old, $new);
    if ($res === false) {
        $err = new \Exception("Failed to rename $old to $new");
        $cb($err, null);
    } else {
        $cb(null, null);
    }
};

$unlinkImpl = function($file, $cb) {
    if (function_exists('\\Amp\\File\\deleteFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $cb) {
            try {
                \Amp\File\deleteFile($file);
                $cb(null, null);
            } catch (\Throwable $err) {
                if (file_exists($file)) {
                    $cb($err, null);
                } else {
                    $cb(null, null);
                }
            }
        });
        return;
    }

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
