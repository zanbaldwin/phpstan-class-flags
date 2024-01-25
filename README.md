# PHPStan: Enforce Class Flags

This library is a [PHPStan](https://github.com/phpstan/phpstan) plugin that
disallows classes with ambiguous class flags (the `final` and `readonly`
keywords) in PHP v8.2+

- **Is Stateful** rule: every class analyzed by PHPStan must either be
  `readonly` or have a `#[HasState]` attribute to explicitly declare its
  statefulness.
- **Is Extendable** rule: every class analyzed by PHPStan must either be `final`
  or have an `#[Extendable]` attribute to explicitly declare its extensibility.

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

To exclude certain classes from having the rules applied, configure the
`classFlagsIgnore` parameter. You may specify concrete implementations,
or you may ignore a range of classes that implement/extend another FQCN.

```neon
parameters:
    classFlagsIgnore:
        stateful:
            concrete:
                - 'App\MySpecificImplementation\SomeConcreteClass'
            instanceOf:
                # You cannot make a class readonly if it extends from a
                # non-readonly class.
                - 'Vendor\Legacy\ConfigBuilderInterface'
        # This option is particularly useful for classes you know will have
        # proxies automatically generated.
        extendable:
            instanceOf:
                # App still uses annotations instead of attributes, and not
                # automatically detected as Doctrine entities.
                - 'App\Entity\PersistableEntityInterface'
```

> Classes that are Doctrine entities/documents, as defined via the attributes
> `#[ORM\Entity]` or `#[ODM\Document]`, are automatically ignored from the _Is
> Extendable_ rule.
