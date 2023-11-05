<?php

namespace Mralston\Eav\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mralston\Eav\Models\EntityAttributeStore;

trait HasEntityAttributeValues
{
    public static function bootHasEntityAttributeValues(): void
    {
        static::retrieved(function (Model $model) {
            $model->load('entityAttributeStore');
        });

        static::saved(function (Model $model) {
            $model->entityAttributeStore->save();
        });
    }

    public function entityAttributeStore(): MorphOne
    {
        return $this->morphOne(EntityAttributeStore::class, 'modelable');
    }

    public function eav(string $key, mixed $value = null): mixed
    {
        if (empty($this->entityAttributeStore)) {
            $this->entityAttributeStore()->create();
            $this->load('entityAttributeStore');
        }

        if (empty($value)) {
            return $this->entityAttributeStore->get($key);
        }

        $this->entityAttributeStore->put($key, $value);
        return null;
    }

    public function getAttribute($key)
    {
        return parent::getAttribute($key) ?? $this->eav($key);
    }

    public function setAttribute($key, $value)
    {
        // The below control flow statements are from the upstream Laravel
        // HasAttributes::setAttribute($key, $value) trait.
        // We're using the same control flow to delegate to Laravel were appropriate.
        // Only when we reach the bottom, do we use our EAV logic instead of filling the
        // attributes array if the model doesn't have the specified attribute key.

        // TODO: There's probably a better way of doing this!




        // Begin piggybacking on Laravel's logic.

        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // this model, such as "json_encoding" a listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return parent::setAttribute($key, $value);
        } elseif ($this->hasAttributeSetMutator($key)) {
            return parent::setAttribute($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif (! is_null($value) && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isEnumCastable($key)) {
            return parent::setAttribute($key, $value);
        }

        if ($this->isClassCastable($key)) {
            return parent::setAttribute($key, $value);
        }

        if (! is_null($value) && $this->isJsonCastable($key)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        // If this attribute contains a JSON ->, we'll set the proper value in the
        // attribute's underlying array. This takes care of properly nesting an
        // attribute in the array's value in the case of deeply nested items.
        if (str_contains($key, '->')) {
            return parent::setAttribute($key, $value);
        }

        if (! is_null($value) && $this->isEncryptedCastable($key)) {
            $value = $this->castAttributeAsEncryptedString($key, $value);
        }

        if (! is_null($value) && $this->hasCast($key, 'hashed')) {
            $value = $this->castAttributeAsHashedString($key, $value);
        }

        // End piggybacking on Laravel's logic.




        // See if the underlying table has a column matching the key
        if ($this->tableHasColumn($key)) {
            // Yes - write to the attributes array
            $this->attributes[$key] = $value;
        } else {
            // No - write to the EAV JSON field
            $this->entityAttributeStore->put($key, $value);
        }

        return $this;
    }

    /**
     * Returns the columns in the database table underlying the model.
     *
     * @return array
     */
    private function getTableColumns(): Collection
    {
        return Cache::remember(
            'table-columns-' . $this->getTable(),
            60,
            fn () => collect(
                $this->getConnection()
                ->getDoctrineSchemaManager()
                ->listTableColumns($this->getTable())
            )
        );
    }

    private function tableHasColumn(string $columName): bool
    {
        return $this->getTableColumns()
            ->filter(fn ($column) => $column->getName() == $columName)
            ->count() > 0;
    }
}