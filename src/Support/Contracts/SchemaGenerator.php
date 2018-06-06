<?php

namespace Nuwave\Lighthouse\Support\Contracts;

use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use Nuwave\Lighthouse\Schema\Utils\DocumentAST;

interface SchemaGenerator extends Directive
{

    /**
     * @param Node $definitionNode
     * @param DocumentAST $current
     * @param DocumentAST $original
     * @param ObjectTypeDefinitionNode|null $parentType
     *
     * @return DocumentAST
     */
    public function handleSchemaGeneration(Node $definitionNode, DocumentAST $current, DocumentAST $original, ObjectTypeDefinitionNode $parentType = null);
}
