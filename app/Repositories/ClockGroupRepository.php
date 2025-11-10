<?php

namespace App\Repositories;

use App\Models\ClockGroup;
use Illuminate\Database\Eloquent\Collection;

class ClockGroupRepository
{
    /**
     * Get all groups ordered by group name
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return ClockGroup::orderBy('groupName')->get();
    }
    
    /**
     * Find a group by ID
     *
     * @param int $id
     * @return ClockGroup
     */
    public function find(int $id): ClockGroup
    {
        return ClockGroup::findOrFail($id);
    }
    
    /**
     * Create a new group
     *
     * @param array $data
     * @return ClockGroup
     */
    public function create(array $data): ClockGroup
    {
        return ClockGroup::create($data);
    }
    
    /**
     * Update a group
     *
     * @param int $id
     * @param array $data
     * @return ClockGroup
     */
    public function update(int $id, array $data): ClockGroup
    {
        $group = ClockGroup::findOrFail($id);
        $group->update($data);
        return $group->fresh();
    }
    
    /**
     * Delete a group
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $group = ClockGroup::findOrFail($id);
        return $group->delete();
    }
}

