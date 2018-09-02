<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class FragmentSpreadNode extends BaseNode implements SelectionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[] */
    public $directives;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::FRAGMENT_SPREAD;

        parent::__construct($vars);
    }
}
