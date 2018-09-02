<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class EnumTypeExtensionNode extends BaseNode implements TypeExtensionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var EnumValueDefinitionNode[]|null */
    public $values;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::ENUM_TYPE_EXTENSION;

        parent::__construct($vars);
    }
}
