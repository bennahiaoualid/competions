<?php

namespace App\Repository\Competition;

use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\Competition\CompetitionRepositoryInterface;


class CompetitionRepository implements CompetitionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    /**
     * Find a competition by its ID.
     * @param int|string $id The ID of the competition to find.
     * @param bool $withLevels Whether to include levels in the query.
     * @return Competition|null The competition if found, null otherwise.
     */
    public function findById(int|string $id, bool $withLevels = false): ?Competition
    {
        $query = Competition::query();
        if ($withLevels) {
            $query->with(["levels" => function ($q){
                $q->orderBy("start_date");
            }]);
        }
        return $query->find($id);
    }

    /**
     * Create a new competition.
     *
     * @param array $data The data to create the competition with.
     * @return Competition The created competition.
     */
    public function create(array $data): Competition
    {
        $data["admin_id"] = Auth::id();
        return  Competition::create($data);
    }

    /**
     * Update a competition and return an array with the resyncCompetitionParticipants flag and the competition object.
     *
     * @param Competition $competition The competition to update.
     * @param array $data The data to update the competition with.
     * @return array An array containing the resyncCompetitionParticipants flag and the updated competition object.
    **/
    public function update(Competition $competition, array $data): array
    {
        $competition->fill($data);
        $resyncCompetitionParticipants = false;

        if($competition->isDirty('age_start') || $competition->isDirty('age_end')){
            $resyncCompetitionParticipants = true;
        }
        $competition->save();
        return [
                'resyncCompetitionParticipants' => $resyncCompetitionParticipants,
                'competition' => $competition
                ];
    }

    /**
     * Delete a competition.
     *
     * @param Competition $competition The competition to delete.
     * @return bool True if the competition was deleted successfully, false otherwise.
     */
    public function delete(Competition $competition): bool
    {
        $result = $competition->delete();
        return $result;
    }

    /**
     * Add users to a competition.
     *
     * @param Competition $competition The competition to add users to.
     * @param array $user_ids The IDs of the users to add.
     * @return bool True if the users were added successfully, false otherwise.
     */
    public function addUsersToCompetition(Competition $competition, array $user_ids): bool
    {
        // 1. Filter out empty/non-numeric IDs and get unique input IDs
        $unique_input_user_ids = array_values(array_unique(array_filter($user_ids, 'is_numeric')));

        if (empty($unique_input_user_ids)) {
            return true; // No valid IDs to process
        }

        // 2. Get IDs of users already attached to the competition
        $existing_user_ids = $competition->users()->allRelatedIds()->toArray();

        // 3. Determine which IDs are genuinely new
        $new_user_ids = array_diff($unique_input_user_ids, $existing_user_ids);

        // 4. If there are new users to add, attach them.
        if (!empty($new_user_ids)) {
            $competition->users()->attach($new_user_ids);
        }

        return true;
    }

    /**
     * Remove a user from a competition.
     *
     * @param Competition $competition The competition to remove the user from.
     * @param int $user_id The ID of the user to remove.
     * @return bool True if the user was removed successfully, false otherwise.
     */   
    public function removeUserFromCompetition(Competition $competition, int $user_id): bool
    {
            $competition->users()->detach($user_id);
            return true;
    }

    /** 
    * Add auditors to a competition.
    *
    * @param Competition $competition The competition to add auditors to.
    * @param array $auditor_ids The IDs of the auditors to add.
    * @return bool True if the auditors were added successfully, false otherwise.
    */
    public function addAuditorsToCompetition(Competition $competition, array $auditor_ids): bool
    {
        // 1. Filter out empty/non-numeric IDs and get unique input IDs
        $unique_input_auditor_ids = array_values(array_unique(array_filter($auditor_ids, 'is_numeric')));

        if (empty($unique_input_auditor_ids)) {
            return true; // No valid IDs to process
        }

        // 2. Get IDs of auditors already attached to the competition
        $existing_auditor_ids = $competition->auditors()->allRelatedIds()->toArray();

        // 3. Determine which IDs are genuinely new
        $new_auditor_ids = array_diff($unique_input_auditor_ids, $existing_auditor_ids);

        // 4. If there are new auditors to add, attach them.
        // This will perform a bulk insert if the pivot model ('admin_competition')
        // does not have timestamps or event listeners forcing individual inserts.
        if (!empty($new_auditor_ids)) {
            $competition->auditors()->attach($new_auditor_ids);
        }

        return true;
    }

    /**
    * Activate a competition.
    *
    * @param Competition $competition The competition to activate.
    * @return bool True if the competition was activated successfully, false otherwise.
    */
    public function activate(Competition $competition): bool
    {
        $competition->status = Competition::STATUS_ACTIVE;
        $result = $competition->save();
        return $result;
    }

    /**
     * Get an admin by ID.
     *
     * @param int $id The ID of the admin to get.
     * @return Admin The admin if found, null otherwise.
     */
    public function getAdmin($id): Admin
    {
        return Admin::findOrFail($id);
    }
}
