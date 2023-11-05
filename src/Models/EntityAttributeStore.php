<?php

namespace Mralston\Eav\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class EntityAttributeStore extends Model
{
    protected $casts = [
        'attribute_values' => 'collection',
    ];

    protected $attributes = [
        'attribute_values' => [],
    ];

    public function modelable(): MorphTo
    {
        return $this->morphTo();
    }

    public function get(string $field)
    {
        return $this->attribute_values->get($field);
    }

    public function set(string $field, mixed $value): void
    {
        $this->attribute_values = $this->attribute_values->put($field, $value);
//        $this->save(); // TODO: Don't save automatically - save when parent entity saves
    }

    public function put(string $field, mixed $value): void
    {
        $this->set($field, $value);
    }

    public function unset(string $field)
    {
        $this->attribute_values->pull($field);
//        $this->save(); // TODO: Don't save automatically - save when parent entity saves
    }
}
