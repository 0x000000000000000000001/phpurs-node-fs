# phpurs-node-fs

> PHPurs checkout: see the [PHP library development guide](https://github.com/0x000000000000000000001/phpurs/blob/main/docs/libraries.md) for the TAST toolchain, FFI conventions and validation commands. Run `./bin/test` from this package with the PHP toolchain prepared.

[![Latest release](http://img.shields.io/github/release/purescript-node/purescript-node-fs.svg)](https://github.com/purescript-node/purescript-node-fs/releases)
[![Build status](https://github.com/purescript-node/purescript-node-fs/workflows/CI/badge.svg?branch=master)](https://github.com/purescript-node/purescript-node-fs/actions?query=workflow%3ACI+branch%3Amaster)
[![Pursuit](https://pursuit.purescript.org/packages/purescript-node-fs/badge)](https://pursuit.purescript.org/packages/purescript-node-fs)

PureScript bindings to node's `fs` module.

## PHP runtime

The implementation is split by the API exposed to PureScript:

- `src/Node/FS/Sync.php` wraps PHP filesystem operations.
- `src/Node/FS/Async.php` uses Amp File and Revolt when available, with PHP
  fallbacks for supported operations. Those fallbacks can complete synchronously.
- `src/Node/FS/Stream.php` provides file-backed compatibility adapters: piping
  copies to a destination file and writing appends to it. Incremental reads and
  backpressure are not implemented.

The asynchronous descriptor operations (`open`, `read`, `write`, `close`),
`mkdtemp`, and `truncate` currently report an unsupported-operation error.
Option records are only partially implemented. The upstream documentation below
describes the bindings rather than complete PHP parity.

## Installation

```
spago install node-fs
```

## Documentation

Module documentation is [published on Pursuit](http://pursuit.purescript.org/packages/purescript-node-fs).

## Test files

`test/fixtures/` contains the maintained test inputs. The copy tests create
randomly named working directories under `test/node-fs-tests/`; these copies are
generated scratch files and are ignored by Git. Existing scratch directories
can be removed after a test run when they are no longer needed for inspection.
