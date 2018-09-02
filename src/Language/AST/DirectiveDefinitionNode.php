<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class DirectiveDefinitionNode extends BaseNode implements TypeSystemDefinitionNode
{
    /** @var NameNode */
    public $name;

    /** @var ArgumentNode[] */
    public $arguments;

    /** @var NameNode[] */
    public $locations;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::DIRECTIVE_DEFINITION;

        parent::__construct($vars);
    }
}
