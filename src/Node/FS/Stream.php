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

        public function destroy($err) {
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

        public function destroy($err) {
            if ($this->file) $this->file->close();
            if ($err) $this->emit('error', $err);
            $this->emit('close');
        }
    }
}


if (!class_exists('PhpursFsReadStream')) {
    class PhpursFsReadStream {
        public $path;
        private $listeners = [];

        public function __construct($path) { $this->path = $path; }

        public function on($event, $cb) { $this->listeners[$event][] = $cb; return $this; }

        public function emit($event, ...$args) {
            foreach (($this->listeners[$event] ?? []) as $cb) { $cb(...$args); }
        }

        public function pipe($w) {
            if (is_object($w) && isset($w->path) && is_string($w->path)) {
                @copy($this->path, $w->path);
            }
            if (class_exists('\\Revolt\\EventLoop')) {
                \Revolt\EventLoop::queue(function() { $this->emit('end'); $this->emit('close'); });
            } else {
                $this->emit('end');
                $this->emit('close');
            }
            return $w;
        }

        public function read() { return null; }
        public function pause() {}
        public function resume() {}
        public function destroy($err = null) { $this->emit('close'); }
    }

    class PhpursFsWriteStream {
        public $path;
        private $listeners = [];

        public function __construct($path) { $this->path = $path; }

        public function on($event, $cb) { $this->listeners[$event][] = $cb; return $this; }

        public function emit($event, ...$args) {
            foreach (($this->listeners[$event] ?? []) as $cb) { $cb(...$args); }
        }

        public function write($data) {
            @file_put_contents($this->path, $data, FILE_APPEND);
            return true;
        }

        public function end() { $this->emit('finish'); }
        public function destroy($err = null) { $this->emit('close'); }
    }
}

$exports['createReadStreamImpl'] = function($path) { return new PhpursFsReadStream($path); };

$exports['createReadStreamOptsImpl'] = function($path, $opts) { return new PhpursFsReadStream($path); };

$exports['fdCreateReadStreamImpl'] = function($fd) { return $fd; };
$exports['fdCreateReadStreamOptsImpl'] = function($fd, $opts) { return $fd; };

$exports['createWriteStreamImpl'] = function($path) { return new PhpursFsWriteStream($path); };

$exports['createWriteStreamOptsImpl'] = function($path, $opts) { return new PhpursFsWriteStream($path); };

$exports['fdCreateWriteStreamImpl'] = function($fd) { return $fd; };
$exports['fdCreateWriteStreamOptsImpl'] = function($fd, $opts) { return $fd; };

return $exports;
