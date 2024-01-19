# PHPStan: Enforce Class Flags

This library is a [PHPStan](https://github.com/phpstan/phpstan) plugin that
disallows classes with ambiguous class flags (the `final` and `readonly`
keywords) in PHP v8.2+

- Every class analyzed by PHPStan must either be `readonly` or have a
  `#[HasState]` attribute to explicitly declare its statefulness. No more
  forgetting `readonly` flags!
- Every class analyzed by PHPStan must either be `final` or have an
  `#[Extendable]` attribute to explicitly declare its extensibility. No more
  forgetting `final` flags!

> You can use the `\HasState` and `\Extendable` classes for convenience, or use
> the [`WeDevelop\PHPStan\ClassFlags\Flag\HasState`](src/Flag/HasState.php) and
> [`WeDevelop\PHPStan\ClassFlags\Flag\Extendable`](src/Flag/Extendable.php)
> attribute classes if you prefer to reference a valid class that exists.

## Installation

```sh
composer require --dev wedevelop/phpstan-class-flags
```

## Configuration

In your `phpstan.neon` (or `phpstan.dist.neon`) configuration, add following
section:

```neon
includes:
    - 'vendor/wedevelop/phpstan-class-flags/rules.neon'
```
