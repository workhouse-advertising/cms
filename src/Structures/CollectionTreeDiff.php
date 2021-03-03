<?php

namespace Statamic\Structures;

class CollectionTreeDiff
{
    protected $added = [];
    protected $removed = [];
    protected $moved = [];
    protected $relocated = [];
    private $positions;

    public function analyze($old, $new)
    {
        if ($old === $new) {
            return $this;
        }

        $old = $this->prepare($old);
        $new = $this->prepare($new);
        $this->positions = $this->preparePositions($old, $new);
        $this->added = $new->keys()->diff($old->keys())->values()->all();
        $this->removed = $old->keys()->diff($new->keys())->values()->all();
        $this->moved = $this->analyzeMoved();
        $this->relocated = $this->analyzeRelocated();

        return $this;
    }

    private function prepare($arr)
    {
        return collect($this->flatten($this->addPaths($arr)));
    }

    private function flatten($arr)
    {
        return collect($arr)->mapWithKeys(function ($item, $i) {
            $results = [$item['entry'] => [
                'path' => $item['path'],
                'index' => $i,
            ]];

            if (isset($item['children'])) {
                $results = $results + $this->flatten($item['children']);
            }

            return $results;
        })->all();
    }

    private function addPaths($arr, $path = '*')
    {
        return collect($arr)->map(function ($item) use ($path) {
            $item['path'] = "$path";

            if (isset($item['children'])) {
                $item['children'] = $this->addPaths($item['children'], $path.'.'.$item['entry']);
            }

            return $item;
        })->all();
    }

    public function hasChanged()
    {
        return (bool) $this->affected();
    }

    public function affected()
    {
        return array_merge($this->removed, $this->added, $this->moved);
    }

    public function added()
    {
        return $this->added;
    }

    public function removed()
    {
        return $this->removed;
    }

    /**
     * Items that have changed positions and their "order" would be affected.
     * An item will not be considered moved if their ancestor moved.
     */
    public function moved()
    {
        return $this->moved;
    }

    /**
     * Items that have changed positions and their "uri" would be affected.
     * An item that has changed postitions and kept the same ancestors will not be considered relocated.
     */
    public function relocated()
    {
        return $this->relocated;
    }

    private function preparePositions($old, $new)
    {
        $positions = [];

        foreach ($old as $id => $item) {
            $positions[$id][] = $item;
        }

        foreach ($new as $id => $item) {
            $positions[$id][] = $item;
        }

        return collect($positions)->filter(function ($positions) {
            return count($positions) > 1;
        });
    }

    private function analyzeMoved()
    {
        return $this->positions->filter(function ($item) {
            [$a, $b] = $item;

            return $a['path'].'.'.$a['index'] !== $b['path'].'.'.$b['index'];
        })->keys()->all();
    }

    private function analyzeRelocated()
    {
        return $this->positions->filter(function ($item) {
            return $item[0]['path'] !== $item[1]['path'];
        })->keys()->all();
    }
}