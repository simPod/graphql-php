<?php

declare(strict_types=1);

namespace GraphQL\Validator\Rules;

use GraphQL\Error\Error;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\AST\VariableNode;
use GraphQL\Validator\ValidationContext;
use function assert;
use function sprintf;

class NoUnusedVariables extends ValidationRule
{
    /** @var VariableDefinitionNode[] */
    public $variableDefs;

    public function getVisitor(ValidationContext $context)
    {
        $this->variableDefs = [];

        return [
            NodeKind::OPERATION_DEFINITION => [
                'enter' => function () {
                    $this->variableDefs = [];
                },
                'leave' => function (OperationDefinitionNode $operation) use ($context) {
                    $variableNameUsed = [];
                    $usages           = $context->getRecursiveVariableUsages($operation);
                    $opName           = $operation->name === null ? null : $operation->name->value;

                    foreach ($usages as $usage) {
                        $node = $usage['node'];
                        assert($node instanceof VariableNode);
                        $variableNameUsed[$node->name->value] = true;
                    }

                    foreach ($this->variableDefs as $variableDef) {
                        $variableName = $variableDef->variable->name->value;

                        if (! empty($variableNameUsed[$variableName])) {
                            continue;
                        }

                        $context->reportError(new Error(
                            self::unusedVariableMessage($variableName, $opName),
                            [$variableDef]
                        ));
                    }
                },
            ],
            NodeKind::VARIABLE_DEFINITION  => function ($def) {
                $this->variableDefs[] = $def;
            },
        ];
    }

    public static function unusedVariableMessage($varName, $opName = null)
    {
        return $opName
            ? sprintf('Variable "$%s" is never used in operation "%s".', $varName, $opName)
            : sprintf('Variable "$%s" is never used.', $varName);
    }
}
