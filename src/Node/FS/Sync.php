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

$exports['accessImpl'] = function($file, $mode) {
    // The PureScript wrapper catches exceptions (try/blush); a returned value
    // would be treated as success.
    if (!file_exists($file)) {
        throw new \Exception("ENOENT: no such file or directory, access '$file'");
    }
    $mode = is_object($mode) ? (int) ($mode->value0 ?? 0) : (int) $mode;
    if ($mode === 2 && !is_writable($file)) {
        throw new \Exception("EACCES: permission denied, access '$file'");
    }
    if ($mode === 4 && !is_readable($file)) {
        throw new \Exception("EACCES: permission denied, access '$file'");
    }
    if ($mode === 1 && !is_executable($file)) {
        throw new \Exception("EACCES: permission denied, access '$file'");
    }
    return null;
};

$exports['copyFileImpl'] = function($src, $dest, $mode) {
    $mode = is_object($mode) ? (int) ($mode->value0 ?? 0) : (int) $mode;
    if ($mode === 1 && file_exists($dest)) {
        throw new \Exception("EEXIST: file already exists, copyfile '$src' -> '$dest'");
    }
    if (!@copy($src, $dest)) {
        throw new \Exception("Failed to copy $src to $dest");
    }
};

$exports['mkdtempImpl'] = function($prefix, $encoding) {
    $dir = rtrim($prefix, '/');
    for ($i = 0; $i < 10; $i++) {
        $candidate = $dir . '/' . substr(bin2hex(random_bytes(4)), 0, 6);
        if (!file_exists($candidate)) {
            if (!@mkdir($candidate, 0777, true)) {
                throw new \Exception("Failed to create temporary directory: $candidate");
            }
            return $candidate;
        }
    }
    throw new \Exception("Failed to create temporary directory for prefix: $prefix");
};

$exports['truncateSyncImpl'] = function($file, $len) {
    $fh = @fopen($file, 'r+');
    if ($fh === false) {
        throw new \Exception("Failed to open for truncate: $file");
    }
    if (!@ftruncate($fh, (int) $len)) {
        fclose($fh);
        throw new \Exception("Failed to truncate: $file");
    }
    fclose($fh);
};

$exports['chownSyncImpl'] = function($file, $uid, $gid) {
    @chown($file, (int) $uid);
    @chgrp($file, (int) $gid);
};

$exports['chmodSyncImpl'] = function($file, $mode) {
    if (!@chmod($file, octdec($mode))) {
        throw new \Exception("Failed to chmod: $file");
    }
};

$exports['statSyncImpl'] = function($file) {
    $stats = @stat($file);
    if ($stats === false) {
        throw new \Exception("ENOENT: no such file or directory, stat '$file'");
    }
    return $stats;
};

$exports['lstatSyncImpl'] = function($file) {
    $stats = @lstat($file);
    if ($stats === false) {
        throw new \Exception("ENOENT: no such file or directory, lstat '$file'");
    }
    return $stats;
};

$exports['linkSyncImpl'] = function($old, $new) {
    if (!@link($old, $new)) {
        throw new \Exception("Failed to link $old to $new");
    }
};

$exports['symlinkSyncImpl'] = function($target, $linkpath, $symlinkType) {
    if (!@symlink($target, $linkpath)) {
        throw new \Exception("Failed to symlink $target to $linkpath");
    }
};

$exports['readlinkSyncImpl'] = function($file) {
    $res = @readlink($file);
    if ($res === false) {
        throw new \Exception("EINVAL: invalid argument, readlink '$file'");
    }
    return $res;
};

$exports['realpathSyncImpl'] = function($path, $cache) {
    $res = @realpath($path);
    if ($res === false) {
        throw new \Exception("ENOENT: no such file or directory, realpath '$path'");
    }
    return $res;
};

$exports['rmdirSyncImpl'] = function($path, $opts) {
    if (!@rmdir($path)) {
        throw new \Exception("Failed to remove directory: $path");
    }
};

$exports['rmSyncImpl'] = function($path, $opts) {
    $remove = function($target) use (&$remove) {
        if (is_dir($target) && !is_link($target)) {
            foreach (scandir($target) as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $remove($target . '/' . $entry);
                }
            }
            @rmdir($target);
        } else {
            @unlink($target);
        }
    };
    $remove($path);
};

$exports['utimesSyncImpl'] = function($file, $atime, $mtime) {
    @touch($file, (int) $mtime, (int) $atime);
};

$exports['existsSyncImpl'] = function($file) {
    return file_exists($file);
};

$exports['openSyncImpl'] = function($file, $flags, $mode) {
    $handle = @fopen($file, $flags);
    if ($handle === false) {
        throw new \Exception("ENOENT: no such file or directory, open '$file'");
    }
    return $handle;
};

$exports['readSyncImpl'] = function($fd, $buffer, $offset, $length, $position) {
    if ($position !== null) {
        $pos = is_object($position) ? (int) ($position->value0 ?? 0) : (int) $position;
        @fseek($fd, $pos);
    }
    $data = @fread($fd, (int) $length);
    if ($data === false) {
        throw new \Exception("Failed to read from file descriptor");
    }
    return strlen($data);
};

$exports['writeSyncImpl'] = function($fd, $buffer, $offset, $length, $position) {
    if ($position !== null) {
        $pos = is_object($position) ? (int) ($position->value0 ?? 0) : (int) $position;
        @fseek($fd, $pos);
    }
    $data = substr($buffer, (int) $offset, (int) $length);
    $written = @fwrite($fd, $data);
    if ($written === false) {
        throw new \Exception("Failed to write to file descriptor");
    }
    return $written;
};

$exports['fsyncSyncImpl'] = function($fd) {
    @fflush($fd);
    if (\function_exists('fsync')) {
        @fsync($fd);
    }
};

$exports['closeSyncImpl'] = function($fd) {
    @fclose($fd);
};

return $exports;
