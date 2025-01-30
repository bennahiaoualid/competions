<?php

namespace App\Repository\Admin;

use App\Http\Helpers\AuditorSaveDelete;
use App\Interface\Admin\AdminRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RoleManipulation;
use Exception;
use App\Traits\RegisterLogs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AdminRepository implements AdminRepositoryInterface
{
    use RegisterLogs, RoleManipulation, CrudOperationNotificationAlert;
    function index(){
        $count = [
            "admin" => Admin::all()->count(),
            "user" => User::all()->count(),
        ];

        return view("pages.admin.dashboard",compact('count'));
    }

    function all(){
        $roles = $this->possibleRoles(Auth::user()->getRoleNames()->first());
        return view("pages.admin.admins.list", compact("roles"));
    }

    public function create(array $data){
        try {
            DB::beginTransaction();
            $admin =  Admin::create($data);
            $admin->roles()->sync([$data['role']]);
            DB::commit();
            return true;
        }
        catch (Exception $exception){
            DB::rollBack();
            $this->registerLogs('Admin creation error: ',$exception);
            return false;
        }

    }

    function edit(Admin $user){
        $roles = $this->possibleRoles(Auth::user()->getRoleNames()->first());
        return view("pages.admin.admins.edit-admin", compact("user", "roles"));
    }

    function update(Admin $admin, array $data){
        try {
            $admin->name = $data['name'];
            $admin->email = $data['email'];
            $admin->roles()->sync([$data['role']]);
            $admin->save();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('Admin updating error: ',$exception);
            return false;
        }
    }

    function delete(Admin $admin){
        try {
            // remove admin from binning auditor in a competitions
            AuditorSaveDelete::deleteAuditor($admin->id);
            $admin->delete();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('Admin deleting error: ',$exception);
            return false;
        }
    }

    function auditCompetitions(array $data): View
    {
        // TODO: Implement auditCompetitions() method.
        $admin = Admin::findorfail(Auth::id());
        // Extract filters from the $data array
        $title = $data['title'] ?? null;
        $startDateFrom = $data['start_date_from'] ?? null ;
        $startDateTo = $data['start_date_to'] ?? null;
        $ageStart = $data['age_start'] ?? null;
        $ageEnd = $data['age_end'] ?? null;
        $status = $data['status'] ?? null;
       if ($data['get']){
           $status = "1";
       }
        // Apply the filters to the competitionsAudit relationship
        $competitions = $admin->competitionsAudit()
            ->with('levels')
            ->title($title)
            ->startDate($startDateFrom, $startDateTo)
            ->ageRange($ageStart, $ageEnd)
            ->status($status)
            ->orderBy("start_date")
            ->paginate(5);
        //$competitions = $admin->competitionsAudit()->with('levels')->paginate(5);
        return view("pages.admin.admins.auditor.audited_competitions",compact('competitions'));
    }

    function auditUsers($level_id): View
    {
        $level_id = base64_decode($level_id);
        $level = Level::findorfail($level_id);
        $admin_id = Auth::id();
        return view("pages.admin.admins.auditor.audited_users",compact('level','admin_id'));
    }

    function auditUserResponses($level_id,$user_identifier): View|\Illuminate\Http\RedirectResponse
    {
        $user = User::where('anonymized_identifier',$user_identifier)->first();
        $level = Level::findorfail($level_id);
        if ($user){
            $questions = Question::with(['responses' => function ($query) use ($user) {
                $query->where('user_id',$user->id);
            }])
                ->where('level_id',$level_id)
                ->get();
            return view('pages.admin.admins.auditor.audited_users_responses_submit', compact('questions', 'user','level'));

        }
        return redirect()->back();
    }

    public function submitAudit(array $responses, $user_id, $level_id){
        $notifications[] = [];
        try{

            $user = User::where('anonymized_identifier',$user_id)->first();
            $level = Level::find($level_id);
            $audit_admin = DB::table('level_admin_user')
                ->where('user_id',$user->id)
                ->where('level_id',$level_id)
                ->first();

            $responses_origin = null;

            if ($user && $level && $audit_admin && $audit_admin->admin_id == Auth::id()){
                // get responses from db
                $responses_origin = Response::with(['question' => function ($query) use ($level) {
                    $query->where('level_id',$level->id);
                }])
                    ->where('user_id',$user->id)
                    ->get();
                $index = 0;
                foreach ($responses_origin as $response){

                    // update only responses that not giving an score yet from the auditor
                    if($response->admin_id === null){
                        // if the giving score are greater than the question max score return error
                        $score = intval($responses[$response->id]);
                        if ($response->question->max_score >= $score){
                            // if response duration greater then question duration the user get 50% of the score
                            if($response->response_duration >= $response->question->duration || $score == 0){
                                $response->score = floatval(number_format($score/2, 2));
                            }else{
                                $final_score =  $score - $response->response_duration / $response->question->duration * $score / 2;
                                $response->score = floatval(number_format($final_score, 2));
                            }
                            $response->admin_id = Auth::id();
                            $response->save();
                            $notifications =array_merge($notifications,$this->generateCustomNotifications(__('messages.validation.success.response_audited',['number'=>$index+1]) ,"success"));
                        }else{
                            $notifications =array_merge($notifications,$this->generateCustomNotifications(__('messages.validation.not_allow.audit_score_greater_then_max',['number'=>$index+1]) ,"error"));
                        }
                    }
                    $index++;
                }
            }

        }catch (Exception $exception){

            $this->registerLogs('audit user response score error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }
}
