<?php

namespace App\Interface\User;

interface UserCompetitionRepositoryInterface
{
    function all(array $data,$user);
    function userCompetition($competition_id);
    function competitionDetail($competition_id);
    function levelDetail($level_id);
    function levelStart($level_id);
    function storeResponse(array $data);
    function userResponses($level_id);
    function competitorsLevelOrder($level_id);
    function competitorsCompetitionOrder($competition_id);

}
