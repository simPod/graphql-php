<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class DocumentNode extends BaseNode
{
    /** @var DefinitionNode[] */
    public $definitions;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::DOCUMENT;

        parent::__construct($vars);
    }
}
