<?php

namespace App\Repositories;

use App\Models\ClockEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClockEventRepository
{
    /**
     * Get clock events for a user within a date range
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getEventsForWeek(int $userId, string $startDate, string $endDate): Collection
    {
        return ClockEvent::where('userId', $userId)
            ->whereBetween('eventTime', [$startDate, $endDate])
            ->orderBy('eventTime')
            ->get();
    }
    
    /**
     * Get the last N events for a user (or all users)
     *
     * @param int|null $userId Optional user ID filter
     * @param int $limit Number of events to return
     * @return Collection
     */
    public function getLastEvents(?int $userId = null, int $limit = 20): Collection
    {
        $query = ClockEvent::with('user')->latest('eventTime');
        
        if ($userId) {
            $query->where('userId', $userId);
        }
        
        return $query->limit($limit)->get();
    }
    
    /**
     * Get the latest event for a user
     *
     * @param int $userId
     * @return ClockEvent|null
     */
    public function getLatestEventForUser(int $userId): ?ClockEvent
    {
        return ClockEvent::where('userId', $userId)
            ->latest('eventTime')
            ->first();
    }

    /**
     * Get the earliest event for a user
     *
     * @param int $userId
     * @return ClockEvent|null
     */
    public function getEarliestEventForUser(int $userId): ?ClockEvent
    {
        return ClockEvent::where('userId', $userId)
            ->oldest('eventTime')
            ->first();
    }
    
    /**
     * Create a new clock event
     *
     * @param array $data
     * @return ClockEvent
     */
    public function create(array $data): ClockEvent
    {
        return ClockEvent::create($data);
    }
    
    /**
     * Update a clock event
     *
     * @param int $id
     * @param array $data
     * @return ClockEvent
     */
    public function update(int $id, array $data): ClockEvent
    {
        $clockEvent = ClockEvent::findOrFail($id);
        
        // Log the SQL query for debugging
        DB::enableQueryLog();
        $clockEvent->update($data);
        $queries = DB::getQueryLog();
        
        // Log the last query (the UPDATE)
        if (!empty($queries)) {
            $lastQuery = end($queries);
            Log::info('ClockEvent UPDATE SQL:', [
                'query' => $lastQuery['query'],
                'bindings' => $lastQuery['bindings'],
                'time' => $lastQuery['time']
            ]);
        }
        
        return $clockEvent->fresh();
    }
    
    /**
     * Delete a clock event
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $clockEvent = ClockEvent::findOrFail($id);
        return $clockEvent->delete();
    }
    
    /**
     * Get the event immediately before a date (for week start transition)
     * Returns the last IN event before the date for transition detection
     *
     * @param int $userId
     * @param string $beforeDate
     * @return ClockEvent|null
     */
    public function getEventBeforeDate(int $userId, string $beforeDate): ?ClockEvent
    {
        // First try to get the last IN event before the date (for transition detection)
        $inEvent = ClockEvent::where('userId', $userId)
            ->where('eventTime', '<', $beforeDate)
            ->where('inOrOut', 'IN')
            ->whereNotNull('inOrOut')
            ->where('inOrOut', '!=', '')
            ->latest('eventTime')
            ->first();
        
        // If no IN event found, return the last event (for backward compatibility)
        if (!$inEvent) {
            return ClockEvent::where('userId', $userId)
                ->where('eventTime', '<', $beforeDate)
                ->latest('eventTime')
                ->first();
        }
        
        return $inEvent;
    }
    
    /**
     * Get the event immediately after a date (for week end transition)
     * Returns the first OUT event after the date for transition detection
     *
     * @param int $userId
     * @param string $afterDate
     * @return ClockEvent|null
     */
    public function getEventAfterDate(int $userId, string $afterDate): ?ClockEvent
    {
        // First try to get the first OUT event after the date (for transition detection)
        $outEvent = ClockEvent::where('userId', $userId)
            ->where('eventTime', '>', $afterDate)
            ->where('inOrOut', 'OUT')
            ->whereNotNull('inOrOut')
            ->where('inOrOut', '!=', '')
            ->oldest('eventTime')
            ->first();
        
        // If no OUT event found, return the first event (for backward compatibility)
        if (!$outEvent) {
            return ClockEvent::where('userId', $userId)
                ->where('eventTime', '>', $afterDate)
                ->oldest('eventTime')
                ->first();
        }
        
        return $outEvent;
    }
    
    /**
     * Find a clock event by ID
     *
     * @param int $id
     * @return ClockEvent
     */
    public function find(int $id): ClockEvent
    {
        return ClockEvent::findOrFail($id);
    }
}

