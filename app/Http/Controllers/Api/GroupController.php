<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ClockGroupRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected $groupRepository;

    public function __construct(ClockGroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function index()
    {
        $groups = $this->groupRepository->getAll();
        
        return response()->json([
            'data' => $groups,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'groupName' => 'required|string',
            'weekStartDOW' => 'required|string',
            'weekStartTime' => 'required|string',
        ]);

        $group = $this->groupRepository->create($request->all());

        return response()->json([
            'message' => 'Group created successfully.',
            'data' => $group,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'groupName' => 'required|string',
            'weekStartDOW' => 'required|string',
            'weekStartTime' => 'required|string',
        ]);

        $clockGroup = $this->groupRepository->update($id, $request->all());

        return response()->json([
            'message' => 'Group updated successfully.',
            'data' => $clockGroup,
        ]);
    }

    public function destroy($id)
    {
        $this->groupRepository->delete($id);

        return response()->json([
            'message' => 'Group deleted successfully.',
        ]);
    }
}
