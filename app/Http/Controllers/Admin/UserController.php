<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('users.manage');

        return view('admin.users.index', [
            'users' => $this->userService->paginate($request->string('role')->toString() ?: null),
            'roles' => $this->userService->roleOptions(),
            'filters' => ['role' => $request->string('role')->toString()],
        ]);
    }

    public function create(): View
    {
        $this->authorize('users.manage');

        return view('admin.users.form', [
            'user' => null,
            'roles' => $this->userService->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('users.manage');

        $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', __('hfnms.user_created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('users.manage');

        $user->load('roles');

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->userService->roleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('users.manage');

        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', __('hfnms.user_updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('users.manage');

        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => __('hfnms.cannot_delete_self')]);
        }

        $this->userService->delete($user);

        return redirect()
            ->route('users.index')
            ->with('success', __('hfnms.user_deleted'));
    }
}
