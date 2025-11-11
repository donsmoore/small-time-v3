<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeekTimeService;
use App\Repositories\ClockUserRepository;
use App\Repositories\ClockGroupRepository;
use App\Repositories\ClockEventRepository;
use Illuminate\Http\Request;

class WeekController extends Controller
{
    protected $weekTimeService;
    protected $userRepository;
    protected $groupRepository;
    protected $eventRepository;

    public function __construct(
        WeekTimeService $weekTimeService,
        ClockUserRepository $userRepository,
        ClockGroupRepository $groupRepository,
        ClockEventRepository $eventRepository
    ) {
        $this->weekTimeService = $weekTimeService;
        $this->userRepository = $userRepository;
        $this->groupRepository = $groupRepository;
        $this->eventRepository = $eventRepository;
    }

    public function get(Request $request, $userId)
    {
        try {
            $user = $this->userRepository->find($userId);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $cursorDateTime = $request->input('cursorDateTime', now()->format('Y-m-d H:i:s'));
        
        // Get user's group
        $group = null;
        $weekStartDOW = 'Monday';
        $weekStartTime = '00:00:00';
        
        if ($user->groupId) {
            try {
                $group = $this->groupRepository->find($user->groupId);
                $weekStartDOW = $group->weekStartDOW;
                $weekStartTime = $group->weekStartTime;
            } catch (\Exception $e) {
                // Group not found, use defaults
            }
        }

        // Calculate week start and end using service
        $weekStart = $this->weekTimeService->calculateWorkWeekStart($cursorDateTime, $weekStartDOW, $weekStartTime);
        $weekEnd = $this->weekTimeService->calculateWorkWeekEnd($weekStart);

        // Get events for the week using repository
        $events = $this->eventRepository->getEventsForWeek($userId, $weekStart, $weekEnd);
        
        // Get boundary events for transition detection
        $eventBeforeWeekStart = $this->eventRepository->getEventBeforeDate($userId, $weekStart);
        $eventAfterWeekEnd = $this->eventRepository->getEventAfterDate($userId, $weekEnd);

        // Determine earliest and latest weeks with activity
        $earliestEvent = $this->eventRepository->getEarliestEventForUser($userId);
        $latestEvent = $this->eventRepository->getLatestEventForUser($userId);

        $earliestWeekStart = $earliestEvent
            ? $this->weekTimeService->calculateWorkWeekStart($earliestEvent->eventTime, $weekStartDOW, $weekStartTime)
            : $weekStart;

        $latestWeekStart = $latestEvent
            ? $this->weekTimeService->calculateWorkWeekStart($latestEvent->eventTime, $weekStartDOW, $weekStartTime)
            : $weekStart;

        return response()->json([
            'data' => [
                'userId' => $userId,
                'groupId' => $user->groupId,
                'groupName' => $group ? $group->groupName : '',
                'cursorDateTime' => $cursorDateTime,
                'cursorWeekStartDateTime' => $weekStart,
                'cursorWeekEndDateTime' => $weekEnd,
                'clockWeekStartDOW' => $weekStartDOW,
                'clockWeekStartTime' => $weekStartTime,
                'events' => $events,
                'eventBeforeWeekStart' => $eventBeforeWeekStart,
                'eventAfterWeekEnd' => $eventAfterWeekEnd,
                'earliestWeekStartDateTime' => $earliestWeekStart,
                'latestWeekStartDateTime' => $latestWeekStart,
            ],
        ]);
    }
}
