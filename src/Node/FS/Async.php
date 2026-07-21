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

$exports['accessImpl'] = function($path, $mode, $cb) {
    if (class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            file_exists($path) ? $cb(null, null) : $cb(new \Exception("ENOENT"), null);
        });
        return;
    }
    file_exists($path) ? $cb(null, null) : $cb(new \Exception("ENOENT"), null);
};

$exports['copyFileImpl'] = function($src, $dest, $flags, $cb) {
    if (class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($src, $dest, $cb) {
            @copy($src, $dest) ? $cb(null, null) : $cb(new \Exception("Copy failed"), null);
        });
        return;
    }
    @copy($src, $dest) ? $cb(null, null) : $cb(new \Exception("Copy failed"), null);
};

$exports['mkdtempImpl'] = function($prefix, $opts, $cb) {
    $cb(new \Exception("mkdtempImpl not implemented"), null);
};

$exports['truncateImpl'] = function($path, $len, $cb) {
    $cb(new \Exception("truncateImpl not implemented"), null);
};

$exports['chownImpl'] = function($path, $uid, $gid, $cb) {
    if (function_exists('\\Amp\\File\\changeOwner') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $uid, $gid, $cb) {
            try { \Amp\File\changeOwner($path, $uid, $gid); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @chown($path, $uid) && @chgrp($path, $gid) ? $cb(null, null) : $cb(new \Exception("chown failed"), null);
};

$exports['chmodImpl'] = function($path, $mode, $cb) {
    if (function_exists('\\Amp\\File\\changePermissions') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $mode, $cb) {
            try { \Amp\File\changePermissions($path, $mode); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @chmod($path, $mode) ? $cb(null, null) : $cb(new \Exception("chmod failed"), null);
};

$exports['statImpl'] = function($path, $cb) {
    if (function_exists('\\Amp\\File\\getStatus') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            try { 
                $s = \Amp\File\getStatus($path); 
                if ($s) $cb(null, $s); else $cb(new \Exception("ENOENT"), null);
            } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    $s = @stat($path);
    $s ? $cb(null, $s) : $cb(new \Exception("ENOENT"), null);
};

$exports['lstatImpl'] = function($path, $cb) {
    if (function_exists('\\Amp\\File\\getLinkStatus') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            try { 
                $s = \Amp\File\getLinkStatus($path); 
                if ($s) $cb(null, $s); else $cb(new \Exception("ENOENT"), null);
            } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    $s = @lstat($path);
    $s ? $cb(null, $s) : $cb(new \Exception("ENOENT"), null);
};

$exports['linkImpl'] = function($existing, $new, $cb) {
    if (function_exists('\\Amp\\File\\createHardlink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($existing, $new, $cb) {
            try { \Amp\File\createHardlink($existing, $new); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @link($existing, $new) ? $cb(null, null) : $cb(new \Exception("link failed"), null);
};

$exports['symlinkImpl'] = function($target, $path, $type, $cb) {
    if (function_exists('\\Amp\\File\\createSymlink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($target, $path, $cb) {
            try { \Amp\File\createSymlink($target, $path); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @symlink($target, $path) ? $cb(null, null) : $cb(new \Exception("symlink failed"), null);
};

$exports['readlinkImpl'] = function($path, $opts, $cb) {
    if (function_exists('\\Amp\\File\\readLink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            try { $res = \Amp\File\readLink($path); $cb(null, $res); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    $res = @readlink($path);
    $res !== false ? $cb(null, $res) : $cb(new \Exception("readlink failed"), null);
};

$exports['realpathImpl'] = function($path, $opts, $cb) {
    $res = @realpath($path);
    $res !== false ? $cb(null, $res) : $cb(new \Exception("realpath failed"), null);
};

$exports['rmdirImpl'] = function($path, $cb) {
    if (function_exists('\\Amp\\File\\deleteDirectory') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            try { \Amp\File\deleteDirectory($path); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @rmdir($path) ? $cb(null, null) : $cb(new \Exception("rmdir failed"), null);
};

$exports['rmImpl'] = function($path, $opts, $cb) {
    if (function_exists('\\Amp\\File\\deleteFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $cb) {
            try { \Amp\File\deleteFile($path); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @unlink($path) ? $cb(null, null) : $cb(new \Exception("rm failed"), null);
};

$exports['utimesImpl'] = function($path, $atime, $mtime, $cb) {
    if (function_exists('\\Amp\\File\\touch') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($path, $atime, $mtime, $cb) {
            try { \Amp\File\touch($path, $mtime, $atime); $cb(null, null); } catch (\Throwable $e) { $cb($e, null); }
        });
        return;
    }
    @touch($path, $mtime, $atime) ? $cb(null, null) : $cb(new \Exception("utimes failed"), null);
};

$exports['appendFileImpl'] = function($file, $buff, $opts, $cb) {
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function() use ($file, $buff, $cb) {
            try {
                $handle = \Amp\File\openFile($file, 'a');
                $handle->write($buff);
                $handle->close();
                $cb(null, null);
            } catch (\Throwable $err) {
                $cb($err, null);
            }
        });
        return;
    }
    $res = @\file_put_contents($file, $buff, FILE_APPEND);
    $res !== false ? $cb(null, null) : $cb(new \Exception("appendFile failed"), null);
};

$exports['openImpl'] = function($path, $flags, $mode, $cb) {
    $cb(new \Exception("openImpl not fully implemented"), null);
};

$exports['readImpl'] = function($fd, $buffer, $offset, $length, $position, $cb) {
    $cb(new \Exception("readImpl not fully implemented"), null);
};

$exports['writeImpl'] = function($fd, $buffer, $offset, $length, $position, $cb) {
    $cb(new \Exception("writeImpl not fully implemented"), null);
};

$exports['closeImpl'] = function($fd, $cb) {
    $cb(new \Exception("closeImpl not fully implemented"), null);
};

return $exports;
