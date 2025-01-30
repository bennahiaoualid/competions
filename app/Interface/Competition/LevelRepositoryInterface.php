<?php

namespace App\Interface\Competition;

use App\Models\Competition\Level;

interface LevelRepositoryInterface
{
    function create(array $data);
    function edit($id);
    function update(Level $level , array $data);
    function delete(Level $level);
    function activateLevel($level_id);
    function finishLevel($level_id);

}
