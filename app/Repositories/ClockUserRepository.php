<?php

namespace App\Repositories;

use App\Models\ClockUser;
use Illuminate\Database\Eloquent\Collection;

class ClockUserRepository
{
    /**
     * Get all users ordered by name
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return ClockUser::orderBy('name')->get();
    }
    
    /**
     * Find a user by ID
     *
     * @param int $id
     * @return ClockUser
     */
    public function find(int $id): ClockUser
    {
        return ClockUser::findOrFail($id);
    }
    
    /**
     * Find a user by user code
     *
     * @param string $userCode
     * @return ClockUser|null
     */
    public function findByUserCode(string $userCode): ?ClockUser
    {
        return ClockUser::where('userCode', $userCode)->first();
    }
    
    /**
     * Create a new user
     *
     * @param array $data
     * @return ClockUser
     */
    public function create(array $data): ClockUser
    {
        return ClockUser::create($data);
    }
    
    /**
     * Update a user
     *
     * @param int $id
     * @param array $data
     * @return ClockUser
     */
    public function update(int $id, array $data): ClockUser
    {
        $user = ClockUser::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }
    
    /**
     * Delete a user
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $user = ClockUser::findOrFail($id);
        return $user->delete();
    }
}

