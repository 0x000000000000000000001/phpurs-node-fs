<?php

$exports = [];
$exports['readFileImpl'] = function ($file, $options, $callback) {
    if (function_exists('\\Amp\\File\\read') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($file, $callback) {
            try {
                $result = \Amp\File\read($file);
                $callback(null, $result);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }

    $result = @file_get_contents($file);
    if ($result === false) {
        $error = new \Exception("Failed to read file: $file");
        $callback($error, null);
    } else {
        $callback(null, $result);
    }
};

$exports['writeFileImpl'] = function ($file, $buffer, $options, $callback) {
    if (function_exists('\\Amp\\File\\write') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($file, $buffer, $callback) {
            try {
                \Amp\File\write($file, $buffer);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }

    $result = \file_put_contents($file, $buffer);
    if ($result === false) {
        $lastError = error_get_last();
        $message =
            "Failed to write file: $file. Error: " .
            ($lastError ? $lastError['message'] : 'Unknown');
        \file_put_contents('php://stderr', $message . "\n");
        $error = new \Exception($message);
        $callback($error, null);
    } else {
        $callback(null, null);
    }
};

$exports['mkdirImpl'] = function ($file, $options, $callback) {
    if (
        function_exists('\\Amp\\File\\createDirectoryRecursively') &&
        class_exists('\\Revolt\\EventLoop')
    ) {
        \Revolt\EventLoop::queue(function () use ($file, $callback) {
            try {
                \Amp\File\createDirectoryRecursively($file, 0777);
                $callback(null, null);
            } catch (\Throwable $error) {
                if (!is_dir($file)) {
                    $callback($error, null);
                } else {
                    $callback(null, null);
                }
            }
        });
        return;
    }

    $result = @mkdir($file, 0777, true);
    if (!$result && !is_dir($file)) {
        $error = new \Exception("Failed to create directory: $file");
        $callback($error, null);
    } else {
        $callback(null, null);
    }
};

$exports['readdirImpl'] = function ($file, $callback) {
    if (function_exists('\\Amp\\File\\listFiles') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($file, $callback) {
            try {
                $result = \Amp\File\listFiles($file);
                $callback(null, array_values($result));
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }

    $result = @scandir($file);
    if ($result === false) {
        $error = new \Exception("Failed to read directory: $file");
        $callback($error, null);
    } else {
        $filtered = array_values(
            array_filter($result, function ($item) {
                return $item !== '.' && $item !== '..';
            }),
        );
        $callback(null, $filtered);
    }
};

$exports['renameImpl'] = function ($oldPath, $newPath, $callback) {
    if (function_exists('\\Amp\\File\\move') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($oldPath, $newPath, $callback) {
            try {
                \Amp\File\move($oldPath, $newPath);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }

    $result = @rename($oldPath, $newPath);
    if ($result === false) {
        $error = new \Exception("Failed to rename $oldPath to $newPath");
        $callback($error, null);
    } else {
        $callback(null, null);
    }
};

$exports['unlinkImpl'] = function ($file, $callback) {
    if (function_exists('\\Amp\\File\\deleteFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($file, $callback) {
            try {
                \Amp\File\deleteFile($file);
                $callback(null, null);
            } catch (\Throwable $error) {
                if (file_exists($file)) {
                    $callback($error, null);
                } else {
                    $callback(null, null);
                }
            }
        });
        return;
    }

    $result = @unlink($file);
    if ($result === false && file_exists($file)) {
        $error = new \Exception("Failed to unlink: $file");
        $callback($error, null);
    } else {
        $callback(null, null);
    }
};

$exports['accessImpl'] = function ($path, $mode, $callback) {
    if (class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            file_exists($path) ? $callback(null, null) : $callback(new \Exception('ENOENT'), null);
        });
        return;
    }
    file_exists($path) ? $callback(null, null) : $callback(new \Exception('ENOENT'), null);
};

$exports['copyFileImpl'] = function ($source, $destination, $flags, $callback) {
    if (class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($source, $destination, $callback) {
            @copy($source, $destination)
                ? $callback(null, null)
                : $callback(new \Exception('Copy failed'), null);
        });
        return;
    }
    @copy($source, $destination)
        ? $callback(null, null)
        : $callback(new \Exception('Copy failed'), null);
};

$exports['mkdtempImpl'] = function ($prefix, $options, $callback) {
    $callback(new \Exception('mkdtempImpl not implemented'), null);
};

$exports['truncateImpl'] = function ($path, $length, $callback) {
    $callback(new \Exception('truncateImpl not implemented'), null);
};

$exports['chownImpl'] = function ($path, $uid, $gid, $callback) {
    if (function_exists('\\Amp\\File\\changeOwner') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $uid, $gid, $callback) {
            try {
                \Amp\File\changeOwner($path, $uid, $gid);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @chown($path, $uid) && @chgrp($path, $gid)
        ? $callback(null, null)
        : $callback(new \Exception('chown failed'), null);
};

$exports['chmodImpl'] = function ($path, $mode, $callback) {
    if (function_exists('\\Amp\\File\\changePermissions') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $mode, $callback) {
            try {
                \Amp\File\changePermissions($path, $mode);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @chmod($path, $mode) ? $callback(null, null) : $callback(new \Exception('chmod failed'), null);
};

$exports['statImpl'] = function ($path, $callback) {
    if (function_exists('\\Amp\\File\\getStatus') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            try {
                $stats = \Amp\File\getStatus($path);
                if ($stats) {
                    $callback(null, $stats);
                } else {
                    $callback(new \Exception('ENOENT'), null);
                }
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    $stats = @stat($path);
    $stats ? $callback(null, $stats) : $callback(new \Exception('ENOENT'), null);
};

$exports['lstatImpl'] = function ($path, $callback) {
    if (function_exists('\\Amp\\File\\getLinkStatus') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            try {
                $stats = \Amp\File\getLinkStatus($path);
                if ($stats) {
                    $callback(null, $stats);
                } else {
                    $callback(new \Exception('ENOENT'), null);
                }
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    $stats = @lstat($path);
    $stats ? $callback(null, $stats) : $callback(new \Exception('ENOENT'), null);
};

$exports['linkImpl'] = function ($existing, $newPath, $callback) {
    if (function_exists('\\Amp\\File\\createHardlink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($existing, $newPath, $callback) {
            try {
                \Amp\File\createHardlink($existing, $newPath);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @link($existing, $newPath)
        ? $callback(null, null)
        : $callback(new \Exception('link failed'), null);
};

$exports['symlinkImpl'] = function ($target, $path, $type, $callback) {
    if (function_exists('\\Amp\\File\\createSymlink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($target, $path, $callback) {
            try {
                \Amp\File\createSymlink($target, $path);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @symlink($target, $path)
        ? $callback(null, null)
        : $callback(new \Exception('symlink failed'), null);
};

$exports['readlinkImpl'] = function ($path, $options, $callback) {
    if (function_exists('\\Amp\\File\\readLink') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            try {
                $result = \Amp\File\readLink($path);
                $callback(null, $result);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    $result = @readlink($path);
    $result !== false
        ? $callback(null, $result)
        : $callback(new \Exception('readlink failed'), null);
};

$exports['realpathImpl'] = function ($path, $options, $callback) {
    $result = @realpath($path);
    $result !== false
        ? $callback(null, $result)
        : $callback(new \Exception('realpath failed'), null);
};

$exports['rmdirImpl'] = function ($path, $callback) {
    if (function_exists('\\Amp\\File\\deleteDirectory') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            try {
                \Amp\File\deleteDirectory($path);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @rmdir($path) ? $callback(null, null) : $callback(new \Exception('rmdir failed'), null);
};

$exports['rmImpl'] = function ($path, $options, $callback) {
    if (function_exists('\\Amp\\File\\deleteFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $callback) {
            try {
                \Amp\File\deleteFile($path);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @unlink($path) ? $callback(null, null) : $callback(new \Exception('rm failed'), null);
};

$exports['utimesImpl'] = function ($path, $atime, $mtime, $callback) {
    if (function_exists('\\Amp\\File\\touch') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($path, $atime, $mtime, $callback) {
            try {
                \Amp\File\touch($path, $mtime, $atime);
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    @touch($path, $mtime, $atime)
        ? $callback(null, null)
        : $callback(new \Exception('utimes failed'), null);
};

$exports['appendFileImpl'] = function ($file, $buffer, $options, $callback) {
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        \Revolt\EventLoop::queue(function () use ($file, $buffer, $callback) {
            try {
                $handle = \Amp\File\openFile($file, 'a');
                $handle->write($buffer);
                $handle->close();
                $callback(null, null);
            } catch (\Throwable $error) {
                $callback($error, null);
            }
        });
        return;
    }
    $result = @\file_put_contents($file, $buffer, FILE_APPEND);
    $result !== false
        ? $callback(null, null)
        : $callback(new \Exception('appendFile failed'), null);
};

$exports['openImpl'] = function ($path, $flags, $mode, $callback) {
    $callback(new \Exception('openImpl not fully implemented'), null);
};

$exports['readImpl'] = function ($handle, $buffer, $offset, $length, $position, $callback) {
    $callback(new \Exception('readImpl not fully implemented'), null);
};

$exports['writeImpl'] = function ($handle, $buffer, $offset, $length, $position, $callback) {
    $callback(new \Exception('writeImpl not fully implemented'), null);
};

$exports['closeImpl'] = function ($handle, $callback) {
    $callback(new \Exception('closeImpl not fully implemented'), null);
};

return $exports;
