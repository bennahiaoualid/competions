<?php

namespace App\Repository\Competition;

use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Carbon\Carbon;
use Exception;
use Illuminate\View\View;


class LevelRepository implements LevelRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    public function create(array $data): void
    {
        try {
            if(Competition::competitionMaxLevelNumbers($data["competition_id"])){
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_max_levels'),"error");;
            }
            else{
                if(Competition::find($data["competition_id"])->start_date->gt(Carbon::parse($data["start_date"]))){
                    $notifications = $this->generateCustomNotifications(__('validation.custom.start_date_gt_competition'),"error");;
                }
                else{
                    $time_confile = Level::hasTimeConflict($data["competition_id"],$data["start_date"],$data["duration"]);
                    if ($time_confile) {
                        $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_time_conflict'),"error");;
                    } else{
                        Level::create($data);
                        $notifications = $this->generateNotifications(true,"saved");
                    }
                }
            }
        }catch (Exception $exception){
            $this->registerLogs('Level creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }

    }

    function edit($id) : View{
        $level = Level::findorfail(base64_decode($id));
        $admins = Admin::all();
        return view("pages.admin.competitions.edit.level_edit",compact("level","admins"));
    }

    function update(Level $level, array $data): void
    {
        try {
            if ($level->active == 0){
                $level->name = $data['name'];
                $level->description = $data['description'];
                $level->start_date = $data['start_date'];
                $level->duration = $data['duration'];
                $level->admin_id = $data['admin_id'];
                $level->save();
                $notifications = $this->generateNotifications(true,"updated");
            }
            else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_level_update'),"error");;
            }
        }catch (Exception $exception){
            $this->registerLogs('Level updating error: ',$exception);
            $notifications = $this->generateNotifications(false,"updated");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

}
