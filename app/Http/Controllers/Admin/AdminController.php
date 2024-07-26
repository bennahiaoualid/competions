<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin\Admin;
use App\Services\Admin\AdminService;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RoleManipulation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;


class AdminController extends Controller
{
    use CrudOperationNotificationAlert;
    use RoleManipulation;
    public function __construct(
        protected AdminService $adminService
    ) {
    }

    function index() : View{
        return $this->adminService->index();
    }

    function getAdminList() : View{
        return $this->adminService->all();
    }

    function showActivity() : View{
        return view("pages.admin.admins.activity");
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param StoreAdminRequest $request The incoming request containing admin data.
     */
    function store(StoreAdminRequest $request) {
        $result = $this->adminService->create($request->validated());

        $notifications = $this->generateNotifications($result != false,"saved");

        // Flash each message to the session
        foreach ($notifications as $notification) {
            session()->flash('messages', session('messages', collect())->push($notification));
        }

        return redirect()->route('admin.list');
    }

    function edit(Request $request){
        return $this->adminService->edit($request->admin);
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param UpdateAdminRequest $request The incoming request containing admin data.
     */
    function update(UpdateAdminRequest $request) : RedirectResponse {
        $admin = Admin::find($request->id);
        if ($admin){
            $result = $this->adminService->update($admin,$request->validated());

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
        $admin = $request->admin;
        $result = $this->adminService->delete($admin);
        $notifications = $this->generateNotifications($result,"deleted");
        foreach ($notifications as $notification) {
            session()->flash('messages', session('messages', collect())->push($notification));
        }
        return Redirect::back();
    }


}
