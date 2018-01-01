<?php
/**
 * PHP version 7.1
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */
namespace PhUml\Parser;

use PhUml\Code\Attribute;
use PhUml\Code\ClassDefinition;
use PhUml\Code\Definition;
use PhUml\Code\InterfaceDefinition;
use PhUml\Code\Method;
use PhUml\Code\Structure;
use PhUml\Code\Variable;

class StructureBuilder
{
    /** @var Structure */
    private $structure;

    public function __construct(Structure $structure = null)
    {
        $this->structure = $structure ?? new Structure();
    }

    public function buildFromDefinitions(Definitions $definitions): Structure
    {
        foreach ($definitions->all() as $definition) {
            if ($definitions->isClass($definition) && !$this->structure->has($definition['class'])) {
                $this->structure->addClass($this->buildClass($definitions, $definition));
            } elseif ($definitions->isInterface($definition) && !$this->structure->has($definition['interface'])) {
                $this->structure->addInterface($this->buildInterface($definitions, $definition));
            }
        }
        return $this->structure;
    }

    private function buildInterface(Definitions $definitions, array $interface): InterfaceDefinition
    {
        return new InterfaceDefinition(
            $interface['interface'],
            $this->buildMethods($interface),
            $this->resolveRelatedInterface($definitions, $interface['extends'])
        );
    }

    private function buildClass(Definitions $definitions, array $class): ClassDefinition
    {
        return new ClassDefinition(
            $class['class'],
            $this->buildAttributes($class),
            $this->buildMethods($class),
            $this->buildInterfaces($definitions, $class['implements']),
            $this->resolveParentClass($definitions, $class['extends'])
        );
    }

    /** @return Method[] */
    private function buildMethods(array $definition): array
    {
        $methods = [];
        foreach ($definition['functions'] as $method) {
            [$name, $modifier, $parameters] = $method;
            $methods[] = new Method($name, $modifier, $this->buildParameters($parameters));
        }
        return $methods;
    }

    /** @return Variable[] */
    private function buildParameters(array $parameters): array
    {
        $params = [];
        foreach ($parameters as $param) {
            $params[] = new Variable($param[1], $param[0]);
        }
        return $params;
    }

    /** @return Attribute[] */
    private function buildAttributes(array $class): array
    {
        $attributes = [];
        foreach ($class['attributes'] as $attribute) {
            [$name, $modifier, $comment] = $attribute;
            $attributes[] = new Attribute($name, $modifier, $this->extractType($comment));
        }
        return $attributes;
    }

    private function extractType(?string $comment): ?string
    {
        $type = null;
        if ($comment === null) {
            return $type;
        }

        $matches = null;
        $arrayExpression = '/^[\s*]*@var\s+array\(\s*(\w+\s*=>\s*)?(\w+)\s*\).*$/m';
        if (preg_match($arrayExpression, $comment, $matches)) {
            $type = $matches[2];
        } else {
            $typeExpression = '/^[\s*]*@var\s+(\S+).*$/m';
            if (preg_match($typeExpression, $comment, $matches)) {
                $type = trim($matches[1]);
            }
        }
        return $type;
    }

    /**
     * @param string[] $implements
     * @return Definition[]
     */
    private function buildInterfaces(Definitions $definitions, array $implements): array
    {
        $interfaces = [];
        foreach ($implements as $interface) {
            $interfaces[] = $this->resolveRelatedInterface($definitions, $interface);
        }
        return $interfaces;
    }

    private function resolveRelatedInterface(Definitions $definitions, ?string $interface): ?Definition
    {
        if ($interface === null) {
            return null;
        }
        if (!$this->structure->has($interface)) {
            $this->structure->addInterface($this->buildInterface(
                $definitions,
                $definitions->get($interface)
            ));
        }
        return $this->structure->get($interface);
    }

    private function resolveParentClass(Definitions $definitions, ?string $parent): ?Definition
    {
        if ($parent === null) {
            return null;
        }
        if (!$this->structure->has($parent)) {
            $this->structure->addClass($this->buildClass($definitions, $definitions->get($parent)));
        }
        return $this->structure->get($parent);
    }
}
