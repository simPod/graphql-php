<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class FragmentDefinitionNode extends BaseNode implements ExecutableDefinitionNode, HasSelectionSet
{
    /** @var NameNode|null */
    public $name;

    /**
     * Note: fragment variable definitions are experimental and may be changed
     * or removed in the future.
     *
     * @var VariableDefinitionNode[]|NodeList
     */
    public $variableDefinitions;

    /** @var NamedTypeNode */
    public $typeCondition;

    /** @var DirectiveNode[]|NodeList */
    public $directives;

    /** @var SelectionSetNode */
    public $selectionSet;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::FRAGMENT_DEFINITION;

        parent::__construct($vars);
    }
}
