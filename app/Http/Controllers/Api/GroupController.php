<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ClockGroupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function getColumnWidth()
    {
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM clockGroup WHERE Field = 'groupName'");
            if (empty($columnInfo)) {
                return response()->json(['width' => 255], 200);
            }
            
            $type = $columnInfo[0]->Type;
            // Extract length from VARCHAR(255) or similar
            preg_match('/\((\d+)\)/', $type, $matches);
            $width = isset($matches[1]) ? (int)$matches[1] : 255;
            
            return response()->json(['width' => $width]);
        } catch (\Exception $e) {
            // Fallback to default if query fails
            return response()->json(['width' => 255], 200);
        }
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
