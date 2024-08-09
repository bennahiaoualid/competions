<?php

namespace App\Repository\User;

use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Models\Competition\Competition;
use Illuminate\View\View;

class UserCompetitionRepository implements UserCompetitionRepositoryInterface
{

    function all(array $data): View
    {
        // TODO: Implement all() method.
        // Apply scopes for filtering

        if (count($data) < 1) {
            $competitions = Competition::all();
        }else{
            $competitions = Competition::query()
                ->title($data['title'])
                ->startDate($data['start_date_from'], $data['start_date_to'])
                ->ageRange($data['age_start'], $data['age_end'])
                ->get();
        }
        // Return the view with competitions data
        return view('pages.user.competitions', compact('competitions'));
    }

    function userCompetition($competition_id)
    {
        // TODO: Implement userCompetition() method.
    }
}
