<?php declare(strict_types=1);

namespace WeDevelop\PHPStan\ClassFlags\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use WeDevelop\PHPStan\ClassFlags\Exclude\ClassExclusionChecker;
use WeDevelop\PHPStan\ClassFlags\Flag\Extendable;
use WeDevelop\PHPStan\ClassFlags\Util\AttributeHelper;

/** @implements Rule<InClassNode> */
final readonly class IsExtendableRule implements Rule
{
    public function __construct(
        private ClassExclusionChecker $classExclusionChecker,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @throws ShouldNotHappenException
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $classReflection = $scope->getClassReflection()?->getNativeReflection()) {
            throw new ShouldNotHappenException(sprintf(
                'Could not construct reflection object for node of type "%s".',
                InClassNode::class,
            ));
        }

        if ($this->classExclusionChecker->shouldIgnoreClass($classReflection->getName())) {
            return [];
        }

        // There's absolute no need to require `doctrine/orm` and `doctrine/mongodb-odm`
        // as dependencies *just* to reference class names.
        $isProxied = AttributeHelper::hasAttributeOnClass($classReflection, [
            // @phpstan-ignore-next-line
            \Doctrine\ORM\Mappings\Entity::class,
            // @phpstan-ignore-next-line
            \Doctrine\ORM\Mappings\Embeddable::class,
            // @phpstan-ignore-next-line
            \Doctrine\ODM\MongoDB\Mapping\Annotations\Document::class,
            // @phpstan-ignore-next-line
            \Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument::class,
        ]);

        // We do not extend from anonymous classes, but neither do we add the final keyword to them either.
        // PHP already prohibits using both abstract and final keywords together.
        // Classes that are going to be proxied via Doctrine shouldn't be final, and it's a hassle to enforce
        // #[HasState] on them when we already have another attribute stating what it is.
        if ($classReflection->isAnonymous() || $classReflection->isAbstract() || $isProxied) {
            return [];
        }

        $isFinal = $classReflection->isFinal();
        $isExtendable = AttributeHelper::hasAttributeOnClass($classReflection, [Extendable::class, \Extendable::class]);

        if ($isExtendable == $isFinal) {
            $error = RuleErrorBuilder::message(sprintf('The extensibility of class "%s" is ambiguous.', $classReflection->getShortName(),))
                ->file($scope->getFile())
                ->line($node->getStartLine());
            $isFinal
                ? $error->addTip(sprintf('Remove one of either final keyword or the "#[%s]" attribute.', \Extendable::class))
                : $error->addTip(sprintf('Explicitly mark it as final or add the "#[%s]" attribute.', \Extendable::class));

            return [$error->build()];
        }

        return [];
    }
}
