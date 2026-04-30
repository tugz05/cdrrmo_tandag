<?php

namespace App\Http\Controllers;

use App\Enums\JToastEnum;
use App\Http\Requests\AdministratorStoreRequest;
use App\Http\Requests\AdministratorUpdateRequest;
use App\Models\User;
use App\Services\AdministratorService;
use App\Traits\JSharedTrait;
use Illuminate\Http\RedirectResponse;

class AdministratorController extends Controller
{
    use JSharedTrait;

    public function __construct(private AdministratorService $administrator) {}

    public function index()
    {
        return inertia(
            'Administrators/Index',
            $this->administrator->index()
        );
    }

    public function store(AdministratorStoreRequest $request): RedirectResponse
    {
        $this->administrator->store($request->validated());

        return redirect()->back()->with(JToastEnum::SUCCESS, 'Staff account created.');
    }

    public function update(AdministratorUpdateRequest $request, User $administrator): RedirectResponse
    {
        $data = $request->validated();
        $data['id'] = $administrator->id;

        $this->administrator->update($data);

        return redirect()->back()->with(JToastEnum::SUCCESS, 'Staff account updated.');
    }

    public function destroy(User $administrator): RedirectResponse
    {
        $this->administrator->destroy((string) $administrator->id);

        return redirect()->back()->with(JToastEnum::SUCCESS, 'Staff account removed.');
    }

    public function restore($id): void
    {
        $this->administrator->restore($id);
    }
}
