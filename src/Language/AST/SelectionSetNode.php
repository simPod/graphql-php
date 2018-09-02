<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class SelectionSetNode extends BaseNode
{
    /** @var SelectionNode[] */
    public $selections;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::SELECTION_SET;

        parent::__construct($vars);
    }
}
