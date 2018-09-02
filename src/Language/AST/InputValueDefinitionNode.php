<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class InputValueDefinitionNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /** @var TypeNode */
    public $type;

    /** @var ValueNode */
    public $defaultValue;

    /** @var DirectiveNode[] */
    public $directives;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::INPUT_VALUE_DEFINITION;

        parent::__construct($vars);
    }
}
