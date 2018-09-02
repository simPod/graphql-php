<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class InlineFragmentNode extends BaseNode implements SelectionNode
{
    /** @var NamedTypeNode */
    public $typeCondition;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var SelectionSetNode */
    public $selectionSet;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::INLINE_FRAGMENT;

        parent::__construct($vars);
    }
}
