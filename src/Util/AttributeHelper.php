<?php declare(strict_types=1);

namespace WeDevelop\PHPStan\ClassFlags\Util;

final readonly class AttributeHelper
{
    /**
     * @param class-string|class-string[] $attributeNames
     * @param \ReflectionClass<object>|\ReflectionEnum<object> $class
     */
    public static function hasAttributeOnClass(
        \ReflectionClass|\ReflectionEnum $class,
        string|array $attributeNames,
    ): bool {
        $attributeNames = (array) $attributeNames;
        foreach ($attributeNames as $attributeName) {
            if (count($class->getAttributes($attributeName)) > 0) {
                return true;
            }
        }
        return false;
    }
}
