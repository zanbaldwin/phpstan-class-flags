<?php declare(strict_types=1);

namespace WeDevelop\PHPStan\ClassFlags;

use Composer\Autoload\ClassLoader;
use WeDevelop\PHPStan\ClassFlags\Flag\HasState;
use function class_alias;
use function class_exists;

#[HasState]
final class DummyAliasAutoloader
{
    private const REAL_NAMESPACE = 'WeDevelop\\PHPStan\\ClassFlags\\Flag\\';
    private const DUMMY_ATTRIBUTES = [
        \HasState::class,
        \Extendable::class,
    ];

    private static ?ClassLoader $composerAutoloader = null;

    private static function requireComposerAutoloader(): ?ClassLoader
    {
        foreach ([
             __DIR__ . '/../../autoload.php',
             __DIR__ . '/vendor/autoload.php',
        ] as $autoloadFile) {
            if (file_exists($autoloadFile) && is_readable($autoloadFile)) {
                return require $autoloadFile;
            }
        }

        return null;
    }

    final public static function loadClass(string $class): void
    {
        if (in_array($class, self::DUMMY_ATTRIBUTES, true)) {
            // The class we are defining is outside the namespace of our library,
            // *always* check if a real one exists first.
            self::$composerAutoloader ??= self::requireComposerAutoloader();
            self::$composerAutoloader?->loadClass($class);
            if (!class_exists($class, false)) {
                class_alias(self::REAL_NAMESPACE . $class, $class);
            }
        }
    }
}

spl_autoload_register([DummyAliasAutoloader::class, 'loadClass']);
