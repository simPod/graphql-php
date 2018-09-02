<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class InputObjectTypeDefinitionNode extends BaseNode implements TypeDefinitionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var InputValueDefinitionNode[]|null */
    public $fields;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::INPUT_OBJECT_TYPE_DEFINITION;

        parent::__construct($vars);
    }
}
