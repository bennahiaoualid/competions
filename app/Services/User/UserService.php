<?php

namespace App\Services\User;

use Exception;
use App\Models\User;
use App\Traits\RegisterLogs;
use App\Helpers\CompetitionsOrder;
use Illuminate\Support\Facades\DB;
use App\Contracts\FlasherInterface;
use App\Jobs\User\SoftDeleteUserJob;
use Illuminate\Support\Facades\Auth;
use App\Services\Monitoring\JobTrackingService;

class UserService
{
    use RegisterLogs;
    /**
     * The service coordinates business logic, validation, filtering, transformation, and notifications.
     * The repository is used for data access only.
     */
    public function __construct(
        protected FlasherInterface $flasher,
        protected JobTrackingService $jobTrackingService
    ) {
    }

    /**
     * Show the user dashboard with latest competitions and stats.
     * Business logic: filtering, transformation, and view rendering.
     */
    public function index()
    {
        $user = Auth::user();
        $competitions = $user->competitions()->orderBy('start_date', 'desc')->limit(3)->get();
        $latestCompetitions = $this->getUserCompetitionsWithRankUsingService($competitions, $user->id);
        $data = [
            'latestCompetitions' => $latestCompetitions,
            'active_comp' => $competitions->where('status', 1)->count(),
            'coming_comp' => $competitions->where('status', 0)->count(),
            'finished_comp' => $competitions->where('status', 2)->count(),
        ];

        return $data;
    }

    /**
     * Create a new user with transaction and notification.
     */
    public function create(array $data)
    {
        try {
            $data['admin_id'] = Auth::id();
            User::create($data);
            $this->flasher->crudSuccess('saved');
            return true;
        } catch (Exception $exception) {
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Update a user with transaction and notification.
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function update(User $user, array $data)
    {
        try {
            $user->name = $data['name'];
            $user->email = $data['email'];

            $user->save();
            $this->flasher->crudSuccess('updated');
            return true;
        } catch (Exception $exception) {
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Delete a user with safe delete, transaction, and notification.
     */
    public function delete(User $user, string $reason)
    {
        try {
            $job = $this->createDeleteJob($user, $reason);
            $this->jobTrackingService->dispatchWithTracking($job);
            $this->flasher->crudSuccess('deleted');
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('UserService::delete', $exception);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    private function getUserCompetitionsWithRankUsingService($competitions, $user_id)
    {
        if(empty($competitions)){
            return [
                'competition' => null,
                'user_rank' => null,
                'total_competitors' => null,
            ];
        }
        
        // Single call to get all data
        $allCompetitorData = CompetitionsOrder::getBatchCompetitorsOrder($competitions);
        // Get total counts separately  
        $totalCounts = $this->getTotalParticipantsCount($competitions->pluck('id')->toArray());
        
        return $competitions->map(function ($competition) use ($user_id, $allCompetitorData, $totalCounts) {
            $competitorData = $allCompetitorData[$competition->id];
            $competitors = $competitorData['users'];
            
            if ($competitors->isNotEmpty()) {
                $userRank = $competitors->search(fn($competitor) => $competitor->id === (int)$user_id);
                $userRank = $userRank !== false ? $userRank + 1 : __('messages.global.not_determinate');
                $totalCompetitors = $competitors->count();
            } else {
                $userRank = __('messages.global.not_determinate');
                $totalCompetitors = $totalCounts[$competition->id] ?? 0;
            }
            
            return [
                'competition' => $competition,
                'user_rank' => $userRank,
                'total_competitors' => $totalCompetitors,
            ];
        });
    }

    private function getTotalParticipantsCount(array $competitionIds): array
    {
        $results = DB::table('competition_user')
            ->select('competition_id', DB::raw('COUNT(*) as total'))
            ->whereIn('competition_id', $competitionIds)
            ->groupBy('competition_id')
            ->get()
            ->pluck('total', 'competition_id')
            ->toArray();
        
        // Ensure all competitions have a count (default to 0)
        return array_merge(
            array_fill_keys($competitionIds, 0),
            $results
        );
    }

    private function createDeleteJob(User $user, string $reason): SoftDeleteUserJob
    {
        return new SoftDeleteUserJob($user, Auth::user(), $reason);
    }

}
