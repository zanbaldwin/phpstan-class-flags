<?php declare(strict_types=1);

namespace WeDevelop\PHPStan\ClassFlags\Exclude;

final readonly class ClassExclusionChecker
{
    /**
     * @param class-string[] $concrete
     * @param class-string[] $instanceOf
     */
    public function __construct(
        public array $concrete = [],
        public array $instanceOf = [],
    ) {}

    /** @param class-string $class */
    public function shouldIgnoreClass(string $class): bool
    {
        if (in_array($class, $this->concrete, true)) {
            return true;
        }

        foreach ($this->instanceOf as $parent) {
            if (is_a($class, $parent, true)) {
                return true;
            }
        }

        return false;
    }
}
