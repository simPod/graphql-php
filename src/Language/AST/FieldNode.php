<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class FieldNode extends BaseNode implements SelectionNode
{
    /** @var NameNode */
    public $name;

    /** @var NameNode|null */
    public $alias;

    /** @var ArgumentNode[]|null */
    public $arguments;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var SelectionSetNode|null */
    public $selectionSet;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::FIELD;

        parent::__construct($vars);
    }
}
