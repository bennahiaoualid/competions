<?php

namespace App\Interface\Competition;

use App\Models\Competition\Level;

interface LevelRepositoryInterface
{
    function create(array $data);
    function edit($id);
    function update(Level $level , array $data);

}
