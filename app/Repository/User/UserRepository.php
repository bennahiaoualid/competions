<?php

namespace App\Repository\User;

use App\Helpers\CompetitionsOrder;
use App\Helpers\UserSafeDelete;
use App\Interface\User\UserRepositoryInterface;
use App\Models\User;
use Exception;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;



class UserRepository implements UserRepositoryInterface
{
    use RegisterLogs;
    function index()
    {
        $user = Auth::user();
        $competitions = $user->competitions()
            ->orderBy('start_date', 'desc')
            ->get();
        //dd($this->getUserCompetitionsWithRank($competitions->take(3),$user->id));
        $data = [
            'latestCompetitions' => $this->getUserCompetitionsWithRank($competitions->take(3),$user->id),
            'active_comp' => $competitions->filter(function ($competition) {
                return $competition->status == 1;
                })->count(),
            'coming_comp' => $competitions->filter(function ($competition) {
                return $competition->status == 0;
                 })->count(),
            'finished_comp' => $competitions->filter(function ($competition) {
                return $competition->status == 2;
                })->count()
        ];
       // dd(count($data['latestCompetitions']));
        return view('pages.user.dashboard', compact('data'));


    }
    function show(){
        return view("pages.admin.users.list");
    }
    function edit($id){
        $user = User::findorfail($id);
        return view("pages.admin.users.edit-user",compact("user"));
    }

    public function create(array $data){
        try {
            $data["admin_id"] = Auth::id();
            User::create($data);
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User creation error: ',$exception);
            return false;
        }

    }

    function update(User $user, array $data){
        try {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->save();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User updating error: ',$exception);
            return false;
        }
    }

    function delete(User $user){
        try {
            UserSafeDelete::deleteUser($user->id);
            $user->delete();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User deleting error: ',$exception);
            return false;
        }
    }

    private function getUserCompetitionsWithRank($competitions,$user_id)
    {

        return $competitions->map(function ($competition) use ($user_id) {
            // Fetch the competitors for this competition and calculate their scores
            $competitors = CompetitionsOrder::getCompetitorsOrder($competition, false, true,false)['users'];
           if(count($competitors) > 0){
               // Determine the rank of the authenticated user
               $userRank = $competitors->search(function ($competitor) use ($user_id) {
                       return $competitor->id == $user_id;
                   }) + 1;
               $total_competitors = $competitors->count();
           }
           else{
               $userRank = __('messages.global.not_determinate');
               $total_competitors = $competition->users()->count();
           }

            return [
                'competition' => $competition,
                'user_rank' => $userRank,
                'total_competitors' => $total_competitors,
            ];
        });
    }
}
