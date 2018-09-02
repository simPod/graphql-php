<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class EnumTypeDefinitionNode extends BaseNode implements TypeDefinitionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[] */
    public $directives;

    /** @var EnumValueDefinitionNode[]|null|NodeList */
    public $values;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::ENUM_TYPE_DEFINITION;

        parent::__construct($vars);
    }
}
