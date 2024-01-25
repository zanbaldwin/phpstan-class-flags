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
use WeDevelop\PHPStan\ClassFlags\Flag\HasState;
use WeDevelop\PHPStan\ClassFlags\Util\AttributeHelper;

/** @implements Rule<InClassNode> */
final readonly class IsStatefulRule implements Rule
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

        // Don't bother with adding #[HasState] to anonymous classes because it'll most likely confuse: are we adding
        // the attribute to the anonymous class or the variable we are assigning it to?
        // Also, don't add it to abstract classes - we only care about concrete implementations.
        if ($classReflection->isAnonymous() || $classReflection->isAbstract()) {
            return [];
        }

        $isReadonly = $classReflection->isReadOnly();
        $isStateful = AttributeHelper::hasAttributeOnClass($classReflection, [HasState::class, \HasState::class]);

        if ($isStateful == $isReadonly) {
            $error = RuleErrorBuilder::message(sprintf('The statefulness of class "%s" is ambiguous.', $classReflection->getShortName(),))
                ->file($scope->getFile())
                ->line($node->getStartLine());
            $isReadonly
                ? $error->addTip(sprintf('Remove one of either readonly keyword or the "#[%s]" attribute.', \HasState::class))
                : $error->addTip(sprintf('Explicitly mark it as readonly or add the "#[%s]" attribute.', \HasState::class));

            return [$error->build()];
        }

        return [];
    }
}
