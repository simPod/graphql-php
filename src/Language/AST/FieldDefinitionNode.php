<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class FieldDefinitionNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /** @var InputValueDefinitionNode[]|NodeList */
    public $arguments;

    /** @var TypeNode */
    public $type;

    /** @var DirectiveNode[]|NodeList */
    public $directives;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::FIELD_DEFINITION;

        parent::__construct($vars);
    }
}
