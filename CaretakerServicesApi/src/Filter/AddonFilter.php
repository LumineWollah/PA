<?php
// src/Filter/AddonFilter.php
namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

final class AddonFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property, 
        $value, 
        QueryBuilder $queryBuilder, 
        QueryNameGeneratorInterface $queryNameGenerator, 
        string $resourceClass, 
        ?Operation $operation = null, 
        array $context = []
    ): void {
        if ('addons' !== $property || null === $value) {
            return;
        }
        $value = json_decode($value, true);

        if (!is_array($value)) {
            throw new InvalidArgumentException('The "addons" filter must be an array.');
        }

        foreach ($value as $addonId) {
            $parameterName = $queryNameGenerator->generateParameterName('addon');
            $queryBuilder
                ->andWhere(":$parameterName MEMBER OF o.addons")
                ->setParameter($parameterName, $addonId);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'addons' => [
                'property' => 'addons',
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => 'Filter entities by their addons.',
            ],
        ];
    }
}
