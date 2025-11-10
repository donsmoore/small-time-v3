<?php

namespace App\Services;

use Carbon\Carbon;

class WeekTimeService
{
    /**
     * Calculate the work week start datetime based on cursor date and group settings
     *
     * @param string $cursorDateTime The cursor datetime (Y-m-d H:i:s format)
     * @param string $weekStartDOW Day of week when week starts (e.g., 'Monday')
     * @param string $weekStartTime Time when week starts (e.g., '00:00:00')
     * @return string Work week start datetime (Y-m-d H:i:s format)
     */
    public function calculateWorkWeekStart(string $cursorDateTime, string $weekStartDOW, string $weekStartTime): string
    {
        $cursorDate = Carbon::parse($cursorDateTime);
        $currentDOW = $cursorDate->format('l'); // Full day name (Monday, Tuesday, etc.)
        
        // If cursor is on the week start day, check if it's before the week start time
        if ($currentDOW === $weekStartDOW) {
            $weekStartDate = $cursorDate->copy()->setTimeFromTimeString($weekStartTime);
            
            // If cursor is before week start time, go back one day
            if ($cursorDate->lt($weekStartDate)) {
                $cursorDate->subDay();
                $currentDOW = $cursorDate->format('l');
            }
        }
        
        // Find the most recent week start day
        while ($currentDOW !== $weekStartDOW) {
            $cursorDate->subDay();
            $currentDOW = $cursorDate->format('l');
        }
        
        // Set the time to the week start time
        $cursorDate->setTimeFromTimeString($weekStartTime);
        
        return $cursorDate->format('Y-m-d H:i:s');
    }
    
    /**
     * Calculate the work week end datetime (7 days after start)
     *
     * @param string $weekStart Work week start datetime (Y-m-d H:i:s format)
     * @return string Work week end datetime (Y-m-d H:i:s format)
     */
    public function calculateWorkWeekEnd(string $weekStart): string
    {
        return Carbon::parse($weekStart)
            ->addDays(7)
            ->format('Y-m-d H:i:s');
    }
}

