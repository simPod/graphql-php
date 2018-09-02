<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ArgumentNode extends BaseNode
{
    /** @var ValueNode */
    public $value;

    /** @var NameNode */
    public $name;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::ARGUMENT;

        parent::__construct($vars);
    }
}
