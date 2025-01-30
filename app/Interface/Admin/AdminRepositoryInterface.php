<?php

namespace App\Interface\Admin;

use App\Models\Admin\Admin;

interface AdminRepositoryInterface
{
    function index();
    function all();
    function create(array $data);
    function edit(Admin $user);
    function update(Admin $admin , array $data);
    function delete(Admin $admin);
    function auditCompetitions(array $data);
    function auditUsers($level_id);
    function auditUserResponses($level_id,$user_identifier);
    public function submitAudit(array $responses, $user_id, $level_id);
}
