# Test Plan

Walk through this list before tagging a release or merging a change that touches `composer.json`, the analyzers, or the test suite. Run every command from the repository root.

## Setup

- [ ] `composer install` finishes without errors and without asking to change the lock file.
- [ ] `composer validate` reports the manifest is valid.
- [ ] `working/` exists after the first run and is not shown in `git status`.

## Automated checks

- [ ] `composer phpcs` reports no errors and no warnings for `src/`, `bundle/`, and `tests/`.
- [ ] `composer phpstan` reports `No errors` at level 10 for `src/`, `bundle/`, and `tests/`.
- [ ] `composer test` passes with no warnings, deprecations, or notices in the output.
- [ ] `composer check` runs all three in order and exits 0.

## Functional coverage

Confirm each area has passing tests under `tests/` that cover the listed behaviors.

- [ ] `ProcessShellArgs`: empty default, constructor list, `setArgs()`, space-joined string form.
- [ ] `ProcessShellEnv`: `.` and the global environment by default, constructor values override globals, `setEnv($vars, false)` keeps only the given values, scalars and `Stringable` become strings, arrays, nulls, and plain objects are dropped, chainable setters.
- [ ] `ProcessShellInput`: null default, string, stream resource, iterator, chainable `setInput()`.
- [ ] `ProcessShellOutput`: `RuntimeException` when no callback is set, callback receives type and message, trim on by default and off on request, chainable setters, `fromArray()` forwards to every member and lets each apply its own trim.
- [ ] `ProcessException`: extends `Gadget\Exception\Exception` and formats `[format, args]` messages.
- [ ] `ProcessShell`: `OUT` and `ERR` match `Symfony\Component\Process\Process`, `create()` applies working directory, environment, and no timeout, `execute()` captures stdout and stderr, returns the exit code, feeds stdin, and runs in the configured directory, `start()` returns a started process, `executeAll()` returns every exit code or throws on the first failure, `startAll()` runs with a concurrency limit and returns exit codes keyed by position, empty list returns empty.
- [ ] `GadgetProcessBundle`: extends `Bundle` and exposes `GadgetProcessExtension` under the alias `gadget_process`.
- [ ] `GadgetProcessExtension`: registers each `src/` class as an autowired, autoconfigured service, and a compiled container builds a `ProcessShell`.

## Consuming the package

- [ ] In a scratch project, `composer require gsu-cetloe/gadget-process-php` installs `gsu-cetloe/gadget-core-php`, `gsu-cetloe/symfony-bundle`, and `symfony/process` 6.4.
- [ ] In a scratch Symfony app with `GadgetProcessBundle` enabled, a service that type-hints `ProcessShell` autowires.

## Known gaps

- `startAll()` respects `maxProcesses`, but the suite does not measure concurrency. A timing-based test was judged too flaky to be worth it. If a concurrency bug turns up, add a test that counts live processes.
- Tests run on whatever PHP is installed locally. Composer resolves dependencies as if PHP were 8.1.0, but the behavior checked here is that of the local interpreter.
