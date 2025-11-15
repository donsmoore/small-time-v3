<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClockEventService;
use App\Repositories\ClockEventRepository;
use App\Rules\ValidDateTime;
use Illuminate\Http\Request;

class ClockEventController extends Controller
{
    protected $clockEventService;
    protected $eventRepository;

    public function __construct(
        ClockEventService $clockEventService,
        ClockEventRepository $eventRepository
    ) {
        $this->clockEventService = $clockEventService;
        $this->eventRepository = $eventRepository;
    }

    public function clockInOut(Request $request)
    {
        $request->validate([
            'userCode' => 'required|string',
        ]);

        try {
            $result = $this->clockEventService->clockInOut($request->userCode);
            
            return response()->json([
                'message' => 'Clock ' . $result['clockOp'] . ' successful for ' . $result['user']->name . '.',
                'clockOp' => $result['clockOp'],
                'clockEvent' => $result['clockEvent'],
                'user' => $result['user'],
                'userId' => $result['user']->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function getLastEvents(Request $request, $userId = null)
    {
        $limit = $request->input('limit', 20);
        $events = $this->eventRepository->getLastEvents($userId, $limit);
        
        return response()->json([
            'data' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:clockUser,id',
            'eventTime' => ['required', 'date', new ValidDateTime()],
            'inOrOut' => 'required|in:IN,OUT',
        ]);

        $clockEvent = $this->eventRepository->create($request->all());

        return response()->json([
            'message' => 'Clock event added successfully.',
            'data' => $clockEvent,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'eventTime' => ['required', 'date', new ValidDateTime()],
        ]);

        $clockEvent = $this->eventRepository->update($id, ['eventTime' => $request->eventTime]);

        return response()->json([
            'message' => 'Clock event updated successfully.',
            'data' => $clockEvent,
        ]);
    }

    public function destroy($id)
    {
        $this->eventRepository->delete($id);

        return response()->json([
            'message' => 'Clock event deleted successfully.',
        ]);
    }
}
