<?php

namespace Nuwave\Lighthouse\Schema\Directives\Fields;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Schema\Utils\DocumentAST;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\SchemaGenerator;
use Nuwave\Lighthouse\Support\DataLoader\Loaders\HasManyLoader;
use Nuwave\Lighthouse\Support\Exceptions\DirectiveException;
use Nuwave\Lighthouse\Support\Traits\CreatesPaginators;
use Nuwave\Lighthouse\Support\Traits\HandlesGlobalId;

class HasManyDirective implements FieldResolver, SchemaGenerator
{
    use CreatesPaginators, HandlesGlobalId;

    /**
     * Name of the directive.
     *
     * @return string
     */
    public static function name()
    {
        return 'hasMany';
    }

    /**
     * @param Node $definitionNode
     * @param DocumentAST $current
     * @param DocumentAST $original
     * @param ObjectTypeDefinitionNode|null $parentType
     *
     * @return DocumentAST
     */
    public function handleSchemaGeneration(Node $definitionNode, DocumentAST $current, DocumentAST $original, ObjectTypeDefinitionNode $parentType = null)
    {
        $resolver = $this->getResolver($definitionNode);

        if (!in_array($resolver, ['default', 'paginator', 'relay', 'connection'])) {
            throw new DirectiveException(sprintf(
                '[%s] is not a valid `type` on `hasMany` directive [`paginator`, `relay`, `default`].',
                $resolver
            ));
        }

        switch ($resolver) {
            case 'paginator':
                return $this->registerPaginator($definitionNode, $current, $original, $parentType);
            case 'connection':
            case 'relay':
                return $this->registerConnection($definitionNode, $current, $original, $parentType);
            default:
                return $current;
        }
    }

    /**
     * Resolve the field directive.
     *
     * @param FieldValue $value
     *
     * @return FieldValue
     */
    public function resolveField(FieldValue $value)
    {
        $relation = $this->getRelationshipName($value->getField());
        $resolver = $this->getResolver($value->getField());

        $scopes = $this->getScopes($value);

        return $value->setResolver(function ($parent, array $args, $context = null, ResolveInfo $info = null) use ($relation, $scopes, $resolver) {
            return graphql()->batch(HasManyLoader::class, $parent->getKey(), array_merge(
                compact('relation', 'parent', 'args', 'scopes'),
                ['type' => $resolver]
            ), HasManyLoader::key($parent, $relation, $info));
        });
    }

    /**
     * Get has many relationship name.
     *
     * @param FieldDefinitionNode $field
     *
     * @return string
     */
    protected function getRelationshipName(FieldDefinitionNode $field)
    {
        return $this->directiveArgValue(
            $this->fieldDirective($field, self::name()),
            'relation',
            $field->name->value
        );
    }

    /**
     * Get resolver type.
     *
     * @param FieldDefinitionNode $field
     *
     * @return string
     */
    protected function getResolver(FieldDefinitionNode $field)
    {
        return $this->directiveArgValue(
            $this->fieldDirective($field, self::name()),
            'type',
            'default'
        );
    }

    /**
     * Get scope(s) to run on connection.
     *
     * @param FieldValue $value
     *
     * @return array
     */
    protected function getScopes(FieldValue $value)
    {
        return $this->directiveArgValue(
            $this->fieldDirective($value->getField(), self::name()),
            'scopes',
            []
        );
    }
}
