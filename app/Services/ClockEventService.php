<?php

namespace App\Services;

use App\Models\ClockEvent;
use App\Models\ClockUser;
use Carbon\Carbon;

class ClockEventService
{
    /**
     * Determine the next clock operation (IN or OUT) for a user
     *
     * @param int $userId The user ID
     * @return string 'IN' or 'OUT'
     */
    public function determineNextClockOperation(int $userId): string
    {
        $lastEvent = ClockEvent::where('userId', $userId)
            ->latest('eventTime')
            ->first();
        
        // If no previous event or last event was OUT, next is IN
        if (!$lastEvent || $lastEvent->inOrOut === 'OUT') {
            return 'IN';
        }
        
        // If last event was IN, next is OUT
        return 'OUT';
    }
    
    /**
     * Clock in or out a user
     *
     * @param string $userCode The user code
     * @return array ['clockEvent' => ClockEvent, 'clockOp' => string, 'user' => ClockUser]
     * @throws \Exception If user not found
     */
    public function clockInOut(string $userCode): array
    {
        $user = ClockUser::where('userCode', $userCode)->first();
        
        if (!$user) {
            throw new \Exception('User not found.');
        }
        
        $clockOp = $this->determineNextClockOperation($user->id);
        
        $clockEvent = ClockEvent::create([
            'userId' => $user->id,
            'inOrOut' => $clockOp,
            'eventTime' => Carbon::now(),
        ]);
        
        return [
            'clockEvent' => $clockEvent,
            'clockOp' => $clockOp,
            'user' => $user,
        ];
    }
}

