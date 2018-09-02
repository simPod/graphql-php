<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class DirectiveNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /** @var ArgumentNode[] */
    public $arguments;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::DIRECTIVE;

        parent::__construct($vars);
    }
}
