<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class StringValueNode extends BaseNode implements ValueNode
{
    /** @var string */
    public $value;

    /** @var bool|null */
    public $block;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::STRING;

        parent::__construct($vars);
    }
}
