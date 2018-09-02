<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class VariableDefinitionNode extends BaseNode implements DefinitionNode
{
    /** @var VariableNode */
    public $variable;

    /** @var TypeNode */
    public $type;

    /** @var ValueNode|null */
    public $defaultValue;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::VARIABLE_DEFINITION;

        parent::__construct($vars);
    }
}
