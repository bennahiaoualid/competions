<?php

namespace App\Http\Controllers\Competition;

use Illuminate\View\View;
use App\Models\Competition\Level;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Models\Competition\Competition;
use Illuminate\Support\Facades\Redirect;
use App\Services\Competition\LevelService;
use App\Traits\CrudOperationNotificationAlert;
use App\Http\Requests\Competition\StoreLevelRequest;
use App\Http\Requests\Competition\UpdateLevelRequest;
use App\Interface\Competition\LevelRepositoryInterface;

class LevelController extends Controller
{
    use CrudOperationNotificationAlert;

    public function __construct(
        protected LevelService $levelService,
        protected LevelRepositoryInterface $levelRepository
    ) {
    }

    /**
     * Handles the storage of a level request and returns a response.
     *
     * @param StoreLevelRequest $request The incoming request containing level data.
     * @param Competition $competition The competition instance resolved by route model binding.
     * @return RedirectResponse
     */
    public function store(StoreLevelRequest $request, Competition $competition): RedirectResponse
    {
        $this->levelService->create($request->validated(), $competition);
        return Redirect::back();
    }

    /**
     * Navigate to view that displays the level information.
     * @param string $encodedId The encoded ID of the level to edit.
     * @return View|RedirectResponse
     */
    public function edit(string $encodedId): View|RedirectResponse
    {
        $result = $this->levelService->getEditData($encodedId);
        
        if ($result['status'] === 'success') {
            return view("pages.admin.competitions.edit.level_edit", [
                'level' => $result['level'],
                'admins' => $result['admins']
            ]);
        }
        
        return Redirect::back();
    }

    /**
     * Handles the updating of a level request and returns a response.
     *
     * @param UpdateLevelRequest $request The incoming request containing level data.
     * @param Level $level The level instance resolved by route model binding.
     * @return RedirectResponse
     */
    public function update(UpdateLevelRequest $request, Level $level): RedirectResponse
    {
        $this->levelService->update($level, $request->validated());
        return Redirect::back();
    }

    /**
     * Handles the deleting of a level request and returns a response.
     *
     * @param Level $level The level instance resolved by route model binding.
     * @return RedirectResponse
     */
    public function delete(Level $level): RedirectResponse
    {
        $this->levelService->delete($level);
        return Redirect::back();
    }

    /**
     * Activates a level and returns a response.
     * @param Level $level The level instance resolved by route model binding.
     * @return RedirectResponse
     */
    public function activateLevel(Level $level): RedirectResponse
    {
        $this->levelService->activateLevel($level);
        return Redirect::back();
    }

    /**
     * Finishes a level and returns a response.
     * @param Level $level The level instance resolved by route model binding.
     * @return RedirectResponse
     */
    public function finishLevel(Level $level): RedirectResponse
    {
        $this->levelService->finishLevel($level);
        return Redirect::back();
    }
}
