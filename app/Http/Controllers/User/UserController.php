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
    public function __construct(
        protected UserService $userService
    ) {
    }

    function show() : View{
        return $this->userService->show();
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param StoreUserRequest $request The incoming request containing admin data.
     */
    function store(StoreUserRequest $request) {
        $result = $this->userService->create($request->validated());

        $notifications = $this->generateNotifications($result != false,"saved");

        // Flash each message to the session
        foreach ($notifications as $notification) {
            session()->flash('messages', session('messages', collect())->push($notification));
        }

        return redirect()->route('admin.users');
    }

    function edit($id){
      return $this->userService->edit($id);
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param UpdateUserRequest $request The incoming request containing admin data.
     */
    function update(UpdateUserRequest $request) : RedirectResponse {
        $user = User::find($request->id);
        if ($user){
            $result = $this->userService->update($user,$request->validated());

            $notifications = $this->generateNotifications($result,"updated");

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
        else{
            $notifications = $this->generateCustomNotifications(__('messages.404.user'),"error");
        }
        return Redirect::back();
    }

    /**
     * Handles the deleting of a user request and returns a response with notifications.
     * @param  Request $request
     * @return RedirectResponse
     */
    function delete(Request $request) : RedirectResponse {
        $user = $request->user;
            $result = $this->userService->delete($user);
            $notifications = $this->generateNotifications($result,"deleted");
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return Redirect::back();
    }


}
