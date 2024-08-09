<?php

namespace App\Interface\Competition;

use App\Models\Competition\Competition;

interface CompetitionRepositoryInterface
{
    function all();
    function create(array $data);
    function edit($id);
    function update(Competition $competition , array $data);
    function getCompetitionUsers($competition_id);
    function removeCompetitionUser($competition_id, $user_id);
    function addCompetitionUsers($competition_id, array $user_ids);

}
