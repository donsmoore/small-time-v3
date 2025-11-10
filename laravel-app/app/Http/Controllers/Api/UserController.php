<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClockUser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = ClockUser::with('group')->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'userCode' => 'required|string|unique:clockUser,userCode',
            'groupId' => 'nullable|integer|exists:clockGroup,id',
        ]);

        $user = ClockUser::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'userCode' => 'required|string|unique:clockUser,userCode,' . $id,
            'groupId' => 'nullable|integer|exists:clockGroup,id',
        ]);

        $user = ClockUser::findOrFail($id);
        $user->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $user = ClockUser::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}

