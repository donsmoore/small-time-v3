<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class ValidDateTime implements Rule
{
    protected $timezone;

    public function __construct($timezone = null)
    {
        $this->timezone = $timezone ?? config('app.timezone', 'America/Chicago');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            // Extract date components from the input string
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})(?:\.(\d+))?$/', $value, $matches)) {
                // Format doesn't match expected pattern
                return false;
            }
            
            $origYear = (int)$matches[1];
            $origMonth = (int)$matches[2];
            $origDay = (int)$matches[3];
            $origHour = (int)$matches[4];
            $origMinute = (int)$matches[5];
            $origSecond = (int)$matches[6];
            
            // Check if this is a valid date (e.g., not Feb 30th)
            if (!checkdate($origMonth, $origDay, $origYear)) {
                return false;
            }
            
            // Check if time components are valid
            if ($origHour > 23 || $origMinute > 59 || $origSecond > 59) {
                return false;
            }
            
            // Try to create the datetime with the exact components
            // Use createFromFormat with strict mode to catch DST gaps
            try {
                $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $origYear . '-' . sprintf('%02d', $origMonth) . '-' . sprintf('%02d', $origDay) . ' ' . sprintf('%02d', $origHour) . ':' . sprintf('%02d', $origMinute) . ':' . sprintf('%02d', $origSecond), $this->timezone);
                
                // Check if Carbon adjusted the time (which happens during DST gaps)
                if ($carbon->year !== $origYear || 
                    $carbon->month !== $origMonth || 
                    $carbon->day !== $origDay ||
                    $carbon->hour !== $origHour ||
                    $carbon->minute !== $origMinute ||
                    $carbon->second !== $origSecond) {
                    // Carbon adjusted the time, which means it's invalid (likely DST gap)
                    return false;
                }
            } catch (\Exception $e) {
                // If creation fails, it might be a DST gap or other invalid time
                return false;
            }
            
            // Additional explicit check for DST spring forward gap
            // In US timezones, DST springs forward on the second Sunday of March at 2:00 AM
            // Times between 2:00:00 AM and 2:59:59.999 AM don't exist on that day
            if ($origMonth === 3 && $origHour === 2) {
                $springForwardDate = $this->getSpringForwardDate($origYear);
                $dateString = sprintf('%04d-%02d-%02d', $origYear, $origMonth, $origDay);
                
                if ($dateString === $springForwardDate) {
                    // This is the spring forward day and time is 2:XX AM - doesn't exist!
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            // If anything fails, the datetime is invalid
            return false;
        }
    }

    /**
     * Get the spring forward date (second Sunday of March) for a given year
     * In US timezones, DST springs forward on the second Sunday of March at 2:00 AM
     *
     * @param int $year
     * @return string Date in Y-m-d format
     */
    protected function getSpringForwardDate($year)
    {
        // Find the first day of March
        $firstDay = Carbon::create($year, 3, 1, 0, 0, 0, $this->timezone);
        
        // Find the first Sunday (dayOfWeek: 0 = Sunday)
        $firstSunday = $firstDay->copy();
        if ($firstDay->dayOfWeek != 0) {
            // If first day is not Sunday, find days until first Sunday
            $daysToAdd = 7 - $firstDay->dayOfWeek;
            $firstSunday->addDays($daysToAdd);
        }
        
        // Second Sunday is 7 days after the first
        $secondSunday = $firstSunday->copy()->addDays(7);
        
        return $secondSunday->format('Y-m-d');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute contains an invalid date or time. This may be due to an invalid date (like February 30th) or a daylight saving time gap (like 2:30 AM on spring forward day when clocks jump from 2:00 AM to 3:00 AM).';
    }
}

