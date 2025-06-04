<?php

namespace App\Http\Controllers\Competition;

use Exception;
use Illuminate\View\View;
use Illuminate\Http\Request;
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
     * Handles the updating of a competition request and returns a response with notifications.
     *
     * @param StoreLevelRequest $request The incoming request containing admin data.
     */
    public function store(StoreLevelRequest $request, Competition $competition): RedirectResponse
    {
        $result = $this->levelService->create($request->validated(), $competition);
        $notificationsToFlash = [];

        if ($result['status'] === 'success') {
            $notificationsToFlash = $this->generateNotifications(true, $result['message_key']);
        } elseif ($result['status'] === 'exception') {
            $notificationsToFlash = $this->generateNotifications(false, $result['message_key']);
        } else {
            $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.general_error');
            if (isset($result['exception']) && app()->environment('local')) {
                $message .= ' ' . $result['exception']->getMessage(); 
            }
            $notificationsToFlash = $this->generateCustomNotifications($message, "error");
        }
        
        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }

        return Redirect::back();
    }

    /**
     * navigate to view that display the level information.
     */
    public function edit(string $encodedId): View|RedirectResponse
    {
        $result = $this->levelService->getEditData($encodedId);
        $notificationsToFlash = [];

        if ($result['status'] === 'success') {
            return view("pages.admin.competitions.edit.level_edit", [
                'level' => $result['level'],
                'admins' => $result['admins']
            ]);
        } else {
            $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.fetch_error_detailed');
            if (isset($result['exception']) && app()->environment('local')) {
                $message .= ' ' . $result['exception']->getMessage(); 
            }
            $notificationsToFlash = $this->generateCustomNotifications($message, "error");
        }

        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }
        return Redirect::back();
    }

    /**
     * Handles the updating of a level request and returns a response with notifications.
     *
     * @param UpdateLevelRequest $request The incoming request containing admin data.
     */
    public function update(UpdateLevelRequest $request): RedirectResponse
    {
        $result = $this->levelService->update((int) $request->id, $request->validated());
        $notificationsToFlash = [];

        if ($result['status'] === 'success') {
            $notificationsToFlash = $this->generateNotifications(true, $result['message_key']);
        } else {
            $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.general_error');
            if (isset($result['exception']) && app()->environment('local')) {
                $message .= ' ' . $result['exception']->getMessage(); 
            }
            $notificationsToFlash = $this->generateCustomNotifications($message, "error");
        }
        
        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }

        return Redirect::back();
    }

    /**
     * Handles the deleting of a level request and returns a response with notifications.
     *
     * @param string $level_id The incoming request containing admin data.
     */
    public function delete(string $encodedLevelId): RedirectResponse
    {
        $notificationsToFlash = [];
        try {
            $level = $this->levelRepository->findDecodedOrFail($encodedLevelId);
            $result = $this->levelService->delete($level->id);

            if ($result['status'] === 'success') {
                $notificationsToFlash = $this->generateNotifications(true, $result['message_key']);
            } else {
                $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.general_error');
                if (isset($result['exception']) && app()->environment('local')) {
                    $message .= ' ' . $result['exception']->getMessage(); 
                }
                $notificationsToFlash = $this->generateCustomNotifications($message, "error");
            }
        } catch (Exception $e) {
            $this->levelService->registerLogs('LevelController delete error - level not found: ', $e);
            $notificationsToFlash = $this->generateCustomNotifications(__('messages.validation.not_found.level'), "error");
        }        

        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }

        return Redirect::back();
    }

    /**
     * @param Request $request The incoming request containing level_id.
     */
    public function activateLevel(Request $request): RedirectResponse
    {
        $request->validate(['level_id' => 'required|integer']);
        $result = $this->levelService->activateLevel((int) $request->level_id);
        $notificationsToFlash = [];

        if ($result['status'] === 'success') {
            $notificationsToFlash = $this->generateNotifications(true, $result['message_key']);
        } else {
            $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.general_error');
            if (isset($result['exception']) && app()->environment('local')) {
                $message .= ' ' . $result['exception']->getMessage(); 
            }
            $notificationsToFlash = $this->generateCustomNotifications($message, "error");
        }

        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }

        return Redirect::back();
    }

    /**
     * @param Request $request The incoming request containing level_id.
     */
    public function finishLevel(Request $request): RedirectResponse
    {
        $request->validate(['level_id' => 'required|integer']);
        $result = $this->levelService->finishLevel((int) $request->level_id);
        $notificationsToFlash = [];

        if ($result['status'] === 'success') {
            $notificationsToFlash = $this->generateNotifications(true, $result['message_key']);
        } else {
            $message = isset($result['message_key']) ? __($result['message_key']) : __('messages.general_error');
            if (isset($result['exception']) && app()->environment('local')) {
                $message .= ' ' . $result['exception']->getMessage(); 
            }
            $notificationsToFlash = $this->generateCustomNotifications($message, "error");
        }

        if (!empty($notificationsToFlash)) {
            session()->flash('messages', $notificationsToFlash);
        }

        return Redirect::back();
    }
}
