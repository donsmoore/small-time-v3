<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ClockUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(ClockUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $users = $this->userRepository->getAll();
        
        return response()->json([
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'userCode' => 'required|string|unique:clockUser,userCode',
            'groupId' => 'nullable|integer',
        ]);

        $user = $this->userRepository->create([
            'name' => $request->name,
            'userCode' => $request->userCode,
            'groupId' => $request->groupId ?? 0,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $clockUser = $this->userRepository->find($id);
        
        $request->validate([
            'name' => 'required|string',
            'userCode' => 'required|string|unique:clockUser,userCode,' . $clockUser->id,
            'groupId' => 'nullable|integer',
        ]);

        $clockUser = $this->userRepository->update($id, [
            'name' => $request->name,
            'userCode' => $request->userCode,
            'groupId' => $request->groupId ?? 0,
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $clockUser,
        ]);
    }

    public function getNameColumnWidth()
    {
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM clockUser WHERE Field = 'name'");
            if (empty($columnInfo)) {
                return response()->json(['width' => 30], 200);
            }

            $type = $columnInfo[0]->Type;
            preg_match('/\((\d+)\)/', $type, $matches);
            $width = isset($matches[1]) ? (int) $matches[1] : 30;

            return response()->json(['width' => $width]);
        } catch (\Exception $e) {
            return response()->json(['width' => 30], 200);
        }
    }

    public function destroy($id)
    {
        $this->userRepository->delete($id);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
