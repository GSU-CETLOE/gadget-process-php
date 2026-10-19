# gadget-process-php

A small wrapper around `symfony/process` for running commands from PHP. You build a `ProcessShell` once with a working directory and an environment, then hand it the command, the input, and a callback for the output. It can run one command and wait, run a list one after another, or keep up to N of them going at once. A Symfony bundle is included so the shell can be autowired into services.

CETLOE at Georgia State University maintains this package. It started as `mjfk23/gadget-process`, written by Melody Kimball.

## Requirements

- PHP 8.1
- `proc_open` enabled, since that is how `symfony/process` starts child processes

## Setup

Install [Composer](https://getcomposer.org/) if you don't have it, then add the package to your project:

```sh
composer require gsu-cetloe/gadget-process-php
```

To work on the library itself, clone the repository and install the development dependencies:

```sh
git clone https://github.com/GSU-CETLOE/gadget-process-php.git
cd gadget-process-php
composer install
```

Your machine does not have to run PHP 8.1. Composer is told to resolve dependencies as if it were PHP 8.1.0 no matter what version runs it, so the lock file always reflects what the library targets. Before a release, run the suite on an actual 8.1 build.

## Development commands

| Command | What it does |
| --- | --- |
| `composer test` | Runs the PHPUnit suite in `tests/`. |
| `composer phpstan` | Runs PHPStan at level 10 over `src/`, `bundle/`, and `tests/`. |
| `composer phpcs` | Checks `src/`, `bundle/`, and `tests/` against PSR-12. |
| `composer check` | Runs all three in order. Run this before you commit. |

Anything generated, such as the PHPStan and PHPUnit caches, lands in `working/`. Git ignores that directory.

## How it works

Everything lives under the `Gadget\Process` namespace. There are five small classes and one exception.

| Class | Holds |
| --- | --- |
| `ProcessShellEnv` | The working directory and environment variables every command starts with. |
| `ProcessShellArgs` | The command and its arguments, as a list of strings. |
| `ProcessShellInput` | What to feed the command on stdin: a string, a stream, an iterator, or nothing. |
| `ProcessShellOutput` | A callback that receives each chunk of stdout or stderr. |
| `ProcessShell` | Puts the four together and runs things. |
| `ProcessException` | Thrown when a command in `executeAll()` exits non-zero. |

### Build a shell

```php
use Gadget\Process\ProcessShell;
use Gadget\Process\ProcessShellEnv;

$shell = new ProcessShell(new ProcessShellEnv('/var/app', ['APP_ENV' => 'prod']));
```

By default the environment is `$_ENV` and `$_SERVER` with your values merged on top. Anything that can't be turned into a string (arrays, plain objects, nulls) is dropped. If you want only the variables you name, pass `false` as the second argument to `setEnv()`:

```php
$env = (new ProcessShellEnv('/var/app'))->setEnv(['PATH' => '/usr/bin'], false);
```

### Run one command

```php
use Gadget\Process\ProcessShellArgs;
use Gadget\Process\ProcessShellInput;
use Gadget\Process\ProcessShellOutput;

$exitCode = $shell->execute(
    new ProcessShellArgs(['git', 'status', '--short']),
    new ProcessShellInput(),
    new ProcessShellOutput(function (string $type, string $message): void {
        // $type is ProcessShell::OUT or ProcessShell::ERR
        echo "[$type] $message\n";
    })
);
```

`execute()` blocks until the command finishes and returns its exit code. Output chunks are trimmed before they reach your callback. Pass `false` as the second constructor argument of `ProcessShellOutput` to get them as-is.

To send something on stdin, give `ProcessShellInput` a string, a stream resource, or a `Traversable` of strings:

```php
$shell->execute(
    new ProcessShellArgs(['sort']),
    new ProcessShellInput("pear\napple\n"),
    $output
);
```

### Run several commands

Each entry is a three-element array of args, input, and output.

```php
$jobs = [
    [new ProcessShellArgs(['make', 'clean']), new ProcessShellInput(), $output],
    [new ProcessShellArgs(['make', 'build']), new ProcessShellInput(), $output],
];

// One after another. Throws ProcessException on the first non-zero exit code.
$exitCodes = $shell->executeAll($jobs);

// Same, but keep going and return every exit code instead.
$exitCodes = $shell->executeAll($jobs, false);

// Up to four at a time (the default). Returns exit codes keyed by each job's
// position in $jobs, in the order they finished.
$exitCodes = $shell->startAll($jobs, maxProcesses: 4);
```

`startAll()` polls the running processes and sleeps `waitInterval` seconds (default 0.01, never less than 0.01) between polls when nothing has changed.

### Send output to more than one place

`ProcessShellOutput::fromArray()` builds one output that forwards every chunk to all of the outputs you give it. Each one keeps its own trim setting.

```php
$output = ProcessShellOutput::fromArray([
    new ProcessShellOutput(fn(string $type, string $message) => $logger->info($message)),
    new ProcessShellOutput(fn(string $type, string $message) => fwrite(STDOUT, $message), false),
]);
```

### Get at the Symfony `Process` directly

`create()` returns an unstarted `Symfony\Component\Process\Process` built from the shell's environment, and `start()` returns a started one. Both are there for the cases where you need `wait()`, `getPid()`, or a timeout, which the shell leaves unset.

```php
$process = $shell->start($args, $input, $output);
$process->wait();
```

## Symfony bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Gadget\Process\GadgetProcessBundle::class => ['all' => true],
];
```

The bundle registers every class under `src/` as an autowired, autoconfigured service. In practice the one you'll inject is `ProcessShell`, which gets a default `ProcessShellEnv` (working directory `.`, global environment):

```php
use Gadget\Process\ProcessShell;

final class ReportBuilder
{
    public function __construct(private ProcessShell $shell)
    {
    }
}
```

To run from a different directory or with a different environment, define your own `ProcessShellEnv` service and let autowiring pick it up.

## Testing

`composer test` runs the suite. The `ProcessShell` tests spawn real child processes using the same PHP interpreter that runs PHPUnit, and the bundle tests compile a real `ContainerBuilder`. [TESTPLAN.md](TESTPLAN.md) has the manual walkthrough.

## License

MIT. See [LICENSE](LICENSE).
