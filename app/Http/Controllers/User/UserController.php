<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\User\UserService;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RoleManipulation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;


class UserController extends Controller
{
    use CrudOperationNotificationAlert;
    use RoleManipulation;
    /**
     * The controller coordinates HTTP request/response and delegates all business logic to the service.
     */
    public function __construct(
        protected UserService $userService
    ) {
    }

    function index() : View{
        $data = $this->userService->index();
        return view('pages.user.dashboard', compact('data'));
    }

    function show() : View{
        return view('pages.admin.users.list');
    }

    /**
     * Handles the storage of an user request and returns a response.
     * @param StoreUserRequest $request
     * @return RedirectResponse
     */
    function store(StoreUserRequest $request) : RedirectResponse {
        $this->userService->create($request->validated());
        return redirect()->back();
    }

    /**
     * Display the edit user form page.
     */
    function edit(User $user): View{
        return view('pages.admin.users.edit-user', compact('user'));
    }

    /**
     * Handles the update of a user and returns back a response.
     * @param UpdateUserRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    function update(UpdateUserRequest $request, User $user) : RedirectResponse {
        $this->userService->update($user,$request->validated());
        return Redirect::back();
    }

    /**
     * Handles the deleting of a user and returns a response.
     * All business logic and notifications are handled in the service.
     */
    function delete(Request $request) : RedirectResponse {
        $user = $request->user;
        $this->userService->delete($user);
        return Redirect::back();
    }
}
