<?php

if (!class_exists('AmphpReadStreamProxy')) {
    class AmphpReadStreamProxy {
        private $file;
        private $listeners = [];
        private $paused = false;
        private $reading = false;

        public function __construct($path, $opts) {
            \Revolt\EventLoop::queue(function() use ($path, $opts) {
                try {
                    $this->file = \Amp\File\openFile($path, 'r');
                    $this->emit('open');
                    $this->startReading();
                } catch (\Throwable $e) {
                    $this->emit('error', $e);
                }
            });
        }

        public function on($event, $cb) {
            $this->listeners[$event][] = $cb;
            if ($event === 'data' && !$this->reading) {
                $this->startReading();
            }
        }

        public function removeListener($event, $cb) {
            if (isset($this->listeners[$event])) {
                $this->listeners[$event] = array_filter($this->listeners[$event], function($c) use ($cb) { return $c !== $cb; });
            }
        }

        public function emit($event, ...$args) {
            foreach (($this->listeners[$event] ?? []) as $cb) {
                $cb(...$args);
            }
        }

        private function startReading() {
            if ($this->reading || !$this->file) return;
            $this->reading = true;

            \Revolt\EventLoop::queue(function() {
                try {
                    while (!$this->paused && null !== $chunk = $this->file->read()) {
                        $this->emit('data', $chunk);
                    }
                    if (!$this->paused) {
                        $this->emit('end');
                        $this->emit('close');
                        $this->file->close();
                    } else {
                        $this->reading = false;
                    }
                } catch (\Throwable $e) {
                    $this->emit('error', $e);
                }
            });
        }

        public function pause() {
            $this->paused = true;
        }

        public function resume() {
            if ($this->paused) {
                $this->paused = false;
                $this->startReading();
            }
        }

        public function destroy($err = null) {
            if ($this->file) $this->file->close();
            if ($err) $this->emit('error', $err);
            $this->emit('close');
        }
        
        public function pipe() {}
    }
}

if (!class_exists('AmphpWriteStreamProxy')) {
    class AmphpWriteStreamProxy {
        private $file;
        private $listeners = [];
        private $queue = [];
        private $opened = false;
        private $flushing = false;

        public function __construct($path, $mode) {
            \Revolt\EventLoop::queue(function() use ($path, $mode) {
                try {
                    $this->file = \Amp\File\openFile($path, $mode);
                    $this->opened = true;
                    $this->emit('open');
                    $this->flush();
                } catch (\Throwable $e) {
                    $this->emit('error', $e);
                }
            });
        }

        public function on($event, $cb) {
            $this->listeners[$event][] = $cb;
        }

        public function removeListener($event, $cb) {
            if (isset($this->listeners[$event])) {
                $this->listeners[$event] = array_filter($this->listeners[$event], function($c) use ($cb) { return $c !== $cb; });
            }
        }

        public function emit($event, ...$args) {
            foreach (($this->listeners[$event] ?? []) as $cb) {
                $cb(...$args);
            }
        }

        public function write($data) {
            $this->queue[] = $data;
            $this->flush();
            return true;
        }

        public function end() {
            $this->queue[] = false;
            $this->flush();
        }

        private function flush() {
            if (!$this->opened || empty($this->queue) || $this->flushing) return;
            $this->flushing = true;
            
            \Revolt\EventLoop::queue(function() {
                try {
                    while (!empty($this->queue)) {
                        $data = array_shift($this->queue);
                        if ($data === false) {
                            $this->file->close();
                            $this->emit('finish');
                            $this->emit('close');
                            $this->flushing = false;
                            return;
                        }
                        $this->file->write($data);
                    }
                } catch (\Throwable $e) {
                    $this->emit('error', $e);
                }
                $this->flushing = false;
            });
        }

        public function destroy($err = null) {
            if ($this->file) $this->file->close();
            if ($err) $this->emit('error', $err);
            $this->emit('close');
        }
    }
}

$exports['createReadStreamImpl'] = function($path) { 
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        return new AmphpReadStreamProxy($path, null);
    }
    return fopen($path, 'r'); 
};

$exports['createReadStreamOptsImpl'] = function($path, $opts) { 
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        return new AmphpReadStreamProxy($path, $opts);
    }
    return fopen($path, 'r'); 
};

$exports['fdCreateReadStreamImpl'] = function($fd) { return $fd; };
$exports['fdCreateReadStreamOptsImpl'] = function($fd, $opts) { return $fd; };

$exports['createWriteStreamImpl'] = function($path) { 
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        return new AmphpWriteStreamProxy($path, 'w');
    }
    return fopen($path, 'w'); 
};

$exports['createWriteStreamOptsImpl'] = function($path, $opts) {
    $flags = isset($opts->flags) ? $opts->flags : 'w';
    $mode = 'w';
    if ($flags === 'a') $mode = 'a';
    elseif ($flags === 'a+') $mode = 'a+';
    
    if (function_exists('\\Amp\\File\\openFile') && class_exists('\\Revolt\\EventLoop')) {
        return new AmphpWriteStreamProxy($path, $mode);
    }
    
    return fopen($path, $mode);
};

$exports['fdCreateWriteStreamImpl'] = function($fd) { return $fd; };
$exports['fdCreateWriteStreamOptsImpl'] = function($fd, $opts) { return $fd; };

return $exports;
