<?php

// File-backed compatibility streams. Pipe copies the source to a file destination;
// these adapters do not implement incremental reads or backpressure.
if (!class_exists('PhpursFsReadStream')) {
    class PhpursFsReadStream
    {
        public $path;
        private $listeners = [];

        public function __construct($path)
        {
            $this->path = $path;
        }

        public function on($event, $callback)
        {
            $this->listeners[$event][] = $callback;
            return $this;
        }

        public function emit($event, ...$args)
        {
            foreach ($this->listeners[$event] ?? [] as $callback) {
                $callback(...$args);
            }
        }

        public function pipe($destination)
        {
            if (
                is_object($destination) &&
                isset($destination->path) &&
                is_string($destination->path)
            ) {
                @copy($this->path, $destination->path);
            }
            if (class_exists('\\Revolt\\EventLoop')) {
                \Revolt\EventLoop::queue(function () {
                    $this->emit('end');
                    $this->emit('close');
                });
            } else {
                $this->emit('end');
                $this->emit('close');
            }
            return $destination;
        }

        public function read()
        {
            return null;
        }
        public function pause() {}
        public function resume() {}
        public function destroy($error = null)
        {
            $this->emit('close');
        }
    }

    class PhpursFsWriteStream
    {
        public $path;
        private $listeners = [];

        public function __construct($path)
        {
            $this->path = $path;
        }

        public function on($event, $callback)
        {
            $this->listeners[$event][] = $callback;
            return $this;
        }

        public function emit($event, ...$args)
        {
            foreach ($this->listeners[$event] ?? [] as $callback) {
                $callback(...$args);
            }
        }

        public function write($data)
        {
            @file_put_contents($this->path, $data, FILE_APPEND);
            return true;
        }

        public function end()
        {
            $this->emit('finish');
        }
        public function destroy($error = null)
        {
            $this->emit('close');
        }
    }
}

$exports['createReadStreamImpl'] = function ($path) {
    return new PhpursFsReadStream($path);
};

$exports['createReadStreamOptsImpl'] = function ($path, $options) {
    return new PhpursFsReadStream($path);
};

$exports['fdCreateReadStreamImpl'] = function ($handle) {
    return $handle;
};
$exports['fdCreateReadStreamOptsImpl'] = function ($handle, $options) {
    return $handle;
};

$exports['createWriteStreamImpl'] = function ($path) {
    return new PhpursFsWriteStream($path);
};

$exports['createWriteStreamOptsImpl'] = function ($path, $options) {
    return new PhpursFsWriteStream($path);
};

$exports['fdCreateWriteStreamImpl'] = function ($handle) {
    return $handle;
};
$exports['fdCreateWriteStreamOptsImpl'] = function ($handle, $options) {
    return $handle;
};

return $exports;
