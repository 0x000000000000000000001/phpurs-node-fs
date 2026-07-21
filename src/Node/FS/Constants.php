<?php

$exports['f_OK'] = 0;
$exports['x_OK'] = 1;
$exports['w_OK'] = 2;
$exports['r_OK'] = 4;
$exports['copyFile_EXCL'] = 1;
$exports['copyFile_FICLONE'] = 2;
$exports['copyFile_FICLONE_FORCE'] = 4;
$exports['appendCopyMode'] = function($l, $r) { return $l | $r; };

return $exports;
