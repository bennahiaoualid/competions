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
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Contracts\FlasherInterface;


class AdminController extends Controller
{
    use CrudOperationNotificationAlert;
    use RoleManipulation;
    public function __construct(
        protected AdminService $adminService,
        protected FlasherInterface $flasher
    ) {
    }

    /**
     * Display Admin Dashboard
     */
    function index() : View{
        $data = $this->adminService->index();
        return view("pages.admin.dashboard",$data);
    }

    /**
     * Display Admins List View
     */
    function getAdminListView() : View{
        $data = $this->adminService->adminList();
        return view("pages.admin.admins.list",$data);
    }

    function showActivity() : View{
        return view("pages.admin.admins.activity");
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param StoreAdminRequest $request The incoming request containing admin data.
     */
    function store(StoreAdminRequest $request) : RedirectResponse {
        $this->adminService->create($request->validated());
        // Notification handled in service
        return redirect()->back();
    }


    /**
     * Display Admin Edit View
     */
    function edit(Request $request){
        $data = $this->adminService->edit($request->admin);
        return view("pages.admin.admins.edit-admin",$data);
    }

    /**
     * Handles the storage of an admin request and returns a response with notifications.
     *
     * @param UpdateAdminRequest $request The incoming request containing admin data.
     */
    function update(UpdateAdminRequest $request) : RedirectResponse {
        $this->adminService->update($request->admin,$request->validated());
        return Redirect::back();
    }

    /**
     * Handles the deleting of a user request and returns a response with notifications.
     * @param  Request $request
     * @return RedirectResponse
     */
    function delete(Request $request) : RedirectResponse {
        $this->adminService->delete($request->admin);
        // Notification handled in service
        return Redirect::back();
    }

}
