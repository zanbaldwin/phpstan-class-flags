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

        // We do not extend from anonymous classes, but neither do we add the final keyword to them either.
        // PHP already prohibits using both abstract and final keywords together.
        if ($classReflection->isAnonymous() || $classReflection->isAbstract()) {
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
