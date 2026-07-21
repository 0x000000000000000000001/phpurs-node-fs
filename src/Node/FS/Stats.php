<?php

$exports['showStatsObj'] = function($s) { return json_encode($s); };
$exports['isBlockDeviceImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0060000); };
$exports['isCharacterDeviceImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0020000); };
$exports['isDirectoryImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0040000); };
$exports['isFIFOImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0010000); };
$exports['isFileImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0100000); };
$exports['isSocketImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0140000); };
$exports['isSymbolicLinkImpl'] = function($s) { return isset($s['mode']) && (($s['mode'] & 0170000) === 0120000); };
$exports['devImpl'] = function($s) { return isset($s['dev']) ? $s['dev'] : 0; };
$exports['inodeImpl'] = function($s) { return isset($s['ino']) ? $s['ino'] : 0; };
$exports['modeImpl'] = function($s) { return isset($s['mode']) ? $s['mode'] : 0; };
$exports['nlinkImpl'] = function($s) { return isset($s['nlink']) ? $s['nlink'] : 0; };
$exports['uidImpl'] = function($s) { return isset($s['uid']) ? $s['uid'] : 0; };
$exports['gidImpl'] = function($s) { return isset($s['gid']) ? $s['gid'] : 0; };
$exports['rdevImpl'] = function($s) { return isset($s['rdev']) ? $s['rdev'] : 0; };
$exports['sizeImpl'] = function($s) { return isset($s['size']) ? $s['size'] : 0; };
$exports['blkSizeImpl'] = function($s) { return isset($s['blksize']) ? $s['blksize'] : 0; };
$exports['blocksImpl'] = function($s) { return isset($s['blocks']) ? $s['blocks'] : 0; };
$exports['accessedTimeMsImpl'] = function($s) { return isset($s['atime']) ? $s['atime'] * 1000 : 0; };
$exports['modifiedTimeMsImpl'] = function($s) { return isset($s['mtime']) ? $s['mtime'] * 1000 : 0; };
$exports['statusChangedTimeMsImpl'] = function($s) { return isset($s['ctime']) ? $s['ctime'] * 1000 : 0; };
$exports['birthtimeMsImpl'] = function($s) { return isset($s['ctime']) ? $s['ctime'] * 1000 : 0; };
$exports['accessedTimeImpl'] = function($s) { return new \DateTime('@' . (isset($s['atime']) ? $s['atime'] : 0)); };
$exports['modifiedTimeImpl'] = function($s) { return new \DateTime('@' . (isset($s['mtime']) ? $s['mtime'] : 0)); };
$exports['statusChangedTimeImpl'] = function($s) { return new \DateTime('@' . (isset($s['ctime']) ? $s['ctime'] : 0)); };
$exports['birthTimeImpl'] = function($s) { return new \DateTime('@' . (isset($s['ctime']) ? $s['ctime'] : 0)); };

return $exports;
