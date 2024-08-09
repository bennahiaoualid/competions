<?php

namespace App\Interface\User;

interface UserCompetitionRepositoryInterface
{
    function all(array $data);
    function userCompetition($competition_id);

}
