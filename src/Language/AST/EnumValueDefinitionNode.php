<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class EnumValueDefinitionNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[] */
    public $directives;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::ENUM_VALUE_DEFINITION;

        parent::__construct($vars);
    }
}
