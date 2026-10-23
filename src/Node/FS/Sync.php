<?php

$exports = [];
$exports['readFileSyncImpl'] = function ($file, $options) {
    $result = @file_get_contents($file);
    if ($result === false) {
        throw new \Exception("Failed to read file: $file");
    }
    return $result;
};

$exports['writeFileSyncImpl'] = function ($file, $buffer, $options) {
    $result = @\file_put_contents($file, $buffer);
    if ($result === false) {
        throw new \Exception("Failed to write file: $file");
    }
};

$exports['mkdirSyncImpl'] = function ($file, $options) {
    $result = @mkdir($file, 0777, true);
    if (!$result && !is_dir($file)) {
        throw new \Exception("Failed to create directory: $file");
    }
};

$exports['readdirSyncImpl'] = function ($file) {
    $result = @scandir($file);
    if ($result === false) {
        throw new \Exception("Failed to read directory: $file");
    }
    return array_values(
        array_filter($result, function ($item) {
            return $item !== '.' && $item !== '..';
        }),
    );
};

$exports['renameSyncImpl'] = function ($oldPath, $newPath) {
    $result = @rename($oldPath, $newPath);
    if ($result === false) {
        throw new \Exception("Failed to rename $oldPath to $newPath");
    }
};

$exports['unlinkSyncImpl'] = function ($file) {
    $result = @unlink($file);
    if ($result === false && file_exists($file)) {
        throw new \Exception("Failed to unlink: $file");
    }
};

$exports['appendFileSyncImpl'] = function ($file, $buffer, $options) {
    $result = @\file_put_contents($file, $buffer, FILE_APPEND);
    if ($result === false) {
        throw new \Exception("Failed to append to file: $file");
    }
};

$exports['accessImpl'] = function ($file, $mode) {
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

$exports['copyFileImpl'] = function ($source, $destination, $mode) {
    $mode = is_object($mode) ? (int) ($mode->value0 ?? 0) : (int) $mode;
    if ($mode === 1 && file_exists($destination)) {
        throw new \Exception("EEXIST: file already exists, copyfile '$source' -> '$destination'");
    }
    if (!@copy($source, $destination)) {
        throw new \Exception("Failed to copy $source to $destination");
    }
};

$exports['mkdtempImpl'] = function ($prefix, $encoding) {
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

$exports['truncateSyncImpl'] = function ($file, $length) {
    $handle = @fopen($file, 'r+');
    if ($handle === false) {
        throw new \Exception("Failed to open for truncate: $file");
    }
    if (!@ftruncate($handle, (int) $length)) {
        fclose($handle);
        throw new \Exception("Failed to truncate: $file");
    }
    fclose($handle);
};

$exports['chownSyncImpl'] = function ($file, $uid, $gid) {
    @chown($file, (int) $uid);
    @chgrp($file, (int) $gid);
};

$exports['chmodSyncImpl'] = function ($file, $mode) {
    if (!@chmod($file, octdec($mode))) {
        throw new \Exception("Failed to chmod: $file");
    }
};

$exports['statSyncImpl'] = function ($file) {
    $stats = @stat($file);
    if ($stats === false) {
        throw new \Exception("ENOENT: no such file or directory, stat '$file'");
    }
    return $stats;
};

$exports['lstatSyncImpl'] = function ($file) {
    $stats = @lstat($file);
    if ($stats === false) {
        throw new \Exception("ENOENT: no such file or directory, lstat '$file'");
    }
    return $stats;
};

$exports['linkSyncImpl'] = function ($oldPath, $newPath) {
    if (!@link($oldPath, $newPath)) {
        throw new \Exception("Failed to link $oldPath to $newPath");
    }
};

$exports['symlinkSyncImpl'] = function ($target, $linkpath, $symlinkType) {
    if (!@symlink($target, $linkpath)) {
        throw new \Exception("Failed to symlink $target to $linkpath");
    }
};

$exports['readlinkSyncImpl'] = function ($file) {
    $result = @readlink($file);
    if ($result === false) {
        throw new \Exception("EINVAL: invalid argument, readlink '$file'");
    }
    return $result;
};

$exports['realpathSyncImpl'] = function ($path, $cache) {
    $result = @realpath($path);
    if ($result === false) {
        throw new \Exception("ENOENT: no such file or directory, realpath '$path'");
    }
    return $result;
};

$exports['rmdirSyncImpl'] = function ($path, $options) {
    if (!@rmdir($path)) {
        throw new \Exception("Failed to remove directory: $path");
    }
};

$exports['rmSyncImpl'] = function ($path, $options) {
    $remove = function ($target) use (&$remove) {
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

$exports['utimesSyncImpl'] = function ($file, $atime, $mtime) {
    @touch($file, (int) $mtime, (int) $atime);
};

$exports['existsSyncImpl'] = function ($file) {
    return file_exists($file);
};

$exports['openSyncImpl'] = function ($file, $flags, $mode) {
    $handle = @fopen($file, $flags);
    if ($handle === false) {
        throw new \Exception("ENOENT: no such file or directory, open '$file'");
    }
    return $handle;
};

$exports['readSyncImpl'] = function ($handle, $buffer, $offset, $length, $position) {
    if ($position !== null) {
        $positionValue = is_object($position) ? (int) ($position->value0 ?? 0) : (int) $position;
        @fseek($handle, $positionValue);
    }
    $data = @fread($handle, (int) $length);
    if ($data === false) {
        throw new \Exception('Failed to read from file descriptor');
    }
    return strlen($data);
};

$exports['writeSyncImpl'] = function ($handle, $buffer, $offset, $length, $position) {
    if ($position !== null) {
        $positionValue = is_object($position) ? (int) ($position->value0 ?? 0) : (int) $position;
        @fseek($handle, $positionValue);
    }
    $data = substr($buffer, (int) $offset, (int) $length);
    $written = @fwrite($handle, $data);
    if ($written === false) {
        throw new \Exception('Failed to write to file descriptor');
    }
    return $written;
};

$exports['fsyncSyncImpl'] = function ($handle) {
    @fflush($handle);
    if (\function_exists('fsync')) {
        @fsync($handle);
    }
};

$exports['closeSyncImpl'] = function ($handle) {
    @fclose($handle);
};

return $exports;
