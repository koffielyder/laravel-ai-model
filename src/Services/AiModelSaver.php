<?php

namespace Koffielyder\LaravelAiModel\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiModelSaver
{
    public function prepareOperations(array $json): array
    {
        $operations = [];
        $modelClasses = config('ai-model.models', []);

        foreach ($json as $key => $data) {
            $modelClass = $this->resolveModelFromKey($key, $modelClasses);
            if (!$modelClass) continue;

            $this->collectEntities($data, $modelClass, $operations);
        }

        return $operations;
    }

    protected function collectEntities(array $entityData, string $modelClass, array &$operations, ?array $comingFrom = null): void
    {
        $relationSchema = $modelClass::relationSchema() ?? [];

        // Build base data + relations for this entity
        $data = collect($entityData)->except(array_keys($relationSchema))->toArray();
        $relations = [];

        // If we came from a parent, apply reverse relation if it exists
        if ($comingFrom && method_exists($modelClass, 'relationSchema')) {
            foreach ($modelClass::relationSchema() as $relKey => $relInfo) {
                if (
                    isset($relInfo['model']) &&
                    $relInfo['model'] === $comingFrom['model']
                ) {
                    $relations[$relKey] = ['name' => $comingFrom['name']];
                }
            }
        }

        // Recurse into any defined child relations
        foreach ($relationSchema as $relationKey => $info) {
            $relatedModel = $info['model'] ?? null;
            if (!$relatedModel || empty($entityData[$relationKey])) {
                continue;
            }

            $relatedItems = is_array($entityData[$relationKey])
                ? $entityData[$relationKey]
                : [$entityData[$relationKey]];

            foreach ($relatedItems as $child) {
                if (!is_array($child)) continue;

                // Add this child to current entity's relation
                if (!empty($child['name'])) {
                    if (array_is_list($entityData[$relationKey])) {
                        $relations[$relationKey][] = ['name' => $child['name']];
                    } else {
                        $relations[$relationKey] = ['name' => $child['name']];
                    }
                }

                // Recurse into child and tell it who it came from
                if (!empty($entityData['name'])) {
                    $this->collectEntities($child, $relatedModel, $operations, [
                        'model' => $modelClass,
                        'name' => $entityData['name'],
                    ]);
                } else {
                    $this->collectEntities($child, $relatedModel, $operations);
                }
            }
        }

        $operations[] = [
            'type' => 'create',
            'model' => $modelClass,
            'data' => $data,
            'relations' => $relations,
        ];
    }



    protected function resolveModelFromKey(string $key, array $modelClasses): ?string
    {
        $singularKey = Str::singular(Str::studly($key));

        foreach ($modelClasses as $modelClass) {
            if (class_basename($modelClass) === $singularKey) {
                return $modelClass;
            }
        }

        return null;
    }

    public function saveFromPreparedJson(array $operations): void
    {
        $saved = [];
        $pending = [];

        DB::transaction(function () use ($operations, &$saved, &$pending) {
            foreach ($operations as $item) {
                if ($item['type'] !== 'create') continue;
            
                $modelClass = $item['model'];
                $data = $item['data'];
                $relations = $item['relations'] ?? [];
            
                if (empty($data['name'])) continue;
            
                // Extract and remove name
                $name = $data['name'];
                unset($data['name']);
            
                // Generate ai_identifier
                $aiIdentifier = $this->generateUniqueIdentifier($modelClass, $name);
            
                /** @var AiModel $instance */
                $instance = new $modelClass();
            
                $instance->name = $name;
                $instance->ai_identifier = $aiIdentifier;
                $instance->data = $data;
                $instance->relations = $relations; // Keep unresolved for now
                $instance->save();
            
                $saved[$modelClass][$name] = $instance;
            }

            // Now resolve deferred entities with unresolved relations
            foreach ($saved as $modelClass => $entitiesByName) {
                foreach ($entitiesByName as $name => $entity) {
                    $originalRelations = $entity->relations ?? [];
                    if ($this->hasUnresolvedRelations($originalRelations)) {

                        $resolved = $this->resolveRelationIdentifiers(
                            $originalRelations,
                            $modelClass,
                            $saved
                        );

                        $entity->relations = $resolved;
                        $entity->save();
                    }
                }
            }
        });
    }

    protected function hasUnresolvedRelations(array $relations): bool
    {
        foreach ($relations as $value) {
            $items = array_is_list($value) ? $value : [$value];
            foreach ($items as $item) {
                if (is_array($item) && isset($item['name'])) {
                    return true;
                }
            }
        }
        return false;
    }


    protected function resolveRelationIdentifiers(array $relations, string $modelClass, array $saved): array
    {
        $resolved = [];
        $schema = $modelClass::relationSchema();

        foreach ($relations as $key => $value) {
            if (!isset($schema[$key])) continue;

            $relatedModel = $schema[$key]['model'] ?? null;
            if (!$relatedModel) continue;

            $items = array_is_list($value) ? $value : [$value];

            $mapped = [];
            foreach ($items as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $resolvedEntity = $saved[$relatedModel][$item['name']] ?? null;
                    if ($resolvedEntity) {
                        $mapped[] = $resolvedEntity->ai_identifier;
                    }
                } elseif (is_string($item)) {
                    $mapped[] = $item;
                }
            }

            $resolved[$key] = array_is_list($value) ? $mapped : ($mapped[0] ?? null);
        }

        return $resolved;
    }



    protected function generateUniqueIdentifier(string $modelClass, string $name): string
    {
        $base = Str::slug($name);
        $identifier = $base;
        $i = 1;

        while ($modelClass::where('ai_identifier', $identifier)->exists()) {
            $identifier = "{$base}-{$i}";
            $i++;
        }

        return $identifier;
    }
}
