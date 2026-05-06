<?php

namespace App\Http\Controllers;

use App\Enums\JToastEnum;
use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index()
    {
        return Inertia::render(
            'Users/Index',
            $this->userService->all()
        );
    }

    public function store(UserStoreRequest $request)
    {
        $this->userService->store($request->validated());

        $message = ($request->validated()['account_type'] ?? 'resident') === 'staff'
            ? 'Mobile staff (rescuer) account created.'
            : 'Resident account created.';

        return redirect()
            ->back()
            ->with(JToastEnum::SUCCESS, $message);
    }

    public function show(User $user)
    {
        return $this->userService->show($user);
    }

    public function verify(string $id)
    {
        $this->userService->verify($id);
    }

    public function destroy(string $id)
    {
        $this->userService->destroy($id);

        return redirect()->back();
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['email', 'phone', 'address']));

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // cdrrmo-tandag.com/api/v1/password
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! \Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect',
            ], 401);
        }

        $user->update([
            'password' => $request->new_password,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully',
        ]);
    }
}
