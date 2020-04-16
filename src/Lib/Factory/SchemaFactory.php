<?php


namespace SwaggerBake\Lib\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use SwaggerBake\Lib\Annotation\SwagEntity;
use SwaggerBake\Lib\Annotation\SwagEntityAttribute;
use SwaggerBake\Lib\Annotation\SwagEntityAttributeHandler;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Exception\SwaggerBakeRunTimeException;
use SwaggerBake\Lib\Model\ExpressiveAttribute;
use SwaggerBake\Lib\Model\ExpressiveModel;
use SwaggerBake\Lib\OpenApi\Schema;
use SwaggerBake\Lib\OpenApi\SchemaProperty;
use SwaggerBake\Lib\Utility\DataTypeConversion;

class SchemaFactory
{
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    public function create(ExpressiveModel $model) : ?Schema
    {
        if (!$this->isEntitySwaggable($model)) {
            return null;
        }

        $docBlock = $this->getDocBlock($model);

        $properties = $this->getProperties($model);

        $schema = new Schema();
        $schema
            ->setName($model->getName())
            ->setDescription($docBlock ? $docBlock->getSummary() : '')
            ->setType('object')
            ->setProperties($properties)
        ;

        $requiredProperties = array_filter($properties, function ($property) {
            return $property->isRequired();
        });

        $schema->setRequired($requiredProperties);

        return $schema;
    }

    private function getProperties(ExpressiveModel $model) : array
    {
        $return = $this->getSwagPropertyAnnotations($model);

        foreach ($model->getAttributes() as $attribute) {
            $name = $attribute->getName();
            if (isset($return[$name])) {
                continue;
            }

            $return[$name] = $this->getSchemaProperty($attribute);
        }

        return $return;
    }

    private function getDocBlock(ExpressiveModel $model) : ?DocBlock
    {
        $entity = $this->getEntityFromNamespaces($model->getName());

        try {
            $instance = new $entity;
            $reflectionClass = new ReflectionClass(get_class($instance));
        } catch (\Exception $e) {
            return null;
        }

        $comments = $reflectionClass->getDocComment();

        if (!$comments) {
            return null;
        }

        $docFactory = DocBlockFactory::createInstance();
        return $docFactory->create($comments);
    }

    private function getEntityFromNamespaces(string $className) : ?string
    {
        $namespaces = $this->config->getNamespaces();

        if (!isset($namespaces['entities']) || !is_array($namespaces['entities'])) {
            throw new SwaggerBakeRunTimeException(
                'Invalid configuration, missing SwaggerBake.namespaces.controllers'
            );
        }

        foreach ($namespaces['entities'] as $namespace) {
            $entity = $namespace . 'Model\Entity\\' . $className;
            if (class_exists($entity, true)) {
                return $entity;
            }
        }

        return null;
    }

    private function getSchemaProperty(ExpressiveAttribute $attribute) : SchemaProperty
    {
        $property = new SchemaProperty();
        $property
            ->setName($attribute->getName())
            ->setType(DataTypeConversion::convert($attribute->getType()))
            ->setReadOnly($attribute->isPrimaryKey())
        ;

        return $property;
    }

    private function getSwagPropertyAnnotations(ExpressiveModel $model) : array
    {
        $return = [];

        $annotations = $this->getClassAnnotations($model);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof SwagEntityAttribute) {
                $schemaProperty = (new SwagEntityAttributeHandler())->getSchemaProperty($annotation);
                $return[$schemaProperty->getName()] = $schemaProperty;
            }
        }

        return $return;
    }

    private function isEntitySwaggable(ExpressiveModel $model) : bool
    {
        $annotations = $this->getClassAnnotations($model);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof SwagEntity) {
                return $annotation->isVisible;
            }
        }

        return true;
    }

    private function getClassAnnotations(ExpressiveModel $model)
    {
        $entity = $this->getEntityFromNamespaces($model->getName());

        try {
            $instance = new $entity;
            $reflectionClass = new ReflectionClass(get_class($instance));
        } catch (\Exception $e) {
            return [];
        }

        $reader = new AnnotationReader();

        $annotations = $reader->getClassAnnotations($reflectionClass);

        if (!is_array($annotations)) {
            return [];
        }

        return $annotations;
    }
}