<?php

namespace App\Http\Controllers\Admin\Doctor;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Doctor\Specialitie;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Doctor\DoctorScheduleDay;
use App\Http\Resources\User\UserResource;
use App\Models\Doctor\DoctorScheduleHour;
use App\Http\Resources\User\UserCollection;
use App\Models\Doctor\DoctorScheduleJoinHour;

class DoctorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::where(DB::raw("CONCAT(users.name,' ',IFNULL(users.surname,''),' ',users.email)"),"like","%".$search."%")
                        // "name","like","%".$search."%"
                        // ->orWhere("surname","like","%".$search."%")
                        // ->orWhere("email","like","%".$search."%")
                        ->orderBy("id","desc")
                        ->whereHas("roles",function($q){
                            $q->where("name","like","%DOCTOR%");
                        })
                        ->get();

        return response()->json([
            "users" => UserCollection::make($users),
        ]);
    }

    public function config() {
        $roles = Role::where("name","like","%DOCTOR%")->get();

        $specialities = Specialitie::where("state",1)->get();

        $hours_days = collect([]);

        $doctor_schedule_hours = DoctorScheduleHour::all();
        foreach ($doctor_schedule_hours->groupBy("hour") as $key => $schedule_hour) {
            $hours_days->push([
                "hour" => $key,
                "format_hour" => Carbon::parse(date("Y-m-d").' '.$key.":00:00")->format("h:i A"),
                "items" => $schedule_hour->map(function($hour_item) {
                    // Y-m-d h:i:s 2023-10-2 00:13:30 -> 12:13:20
                    return [
                        "id" => $hour_item->id,
                        "hour_start" => $hour_item->hour_start,
                        "hour_end" => $hour_item->hour_end,
                        "format_hour_start" => Carbon::parse(date("Y-m-d").' '.$hour_item->hour_start)->format("h:i A"),
                        "format_hour_end" => Carbon::parse(date("Y-m-d").' '.$hour_item->hour_end)->format("h:i A"),
                        "hour" => $hour_item->hour,
                    ];
                }),
            ]);
        }
        return response()->json([
            "roles" => $roles,
            "specialities" => $specialities,
            "hours_days" => $hours_days,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $schedule_hours = json_decode($request->schedule_hours,1);

        $users_is_valid = User::where("email",$request->email)->first();

        if($users_is_valid){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO CON ESTE EMAIL YA EXISTE"
            ]);
        }

        if($request->hasFile("imagen")){
            $path = Storage::putFile("staffs",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }
        // "Fri Oct 08 1993 00:00:00 GMT-0500 (hora estándar de Perú)"
        // Eliminar la parte de la zona horaria (GMT-0500 y entre paréntesis)
        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);

        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        $user = User::create($request->all());

        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        // ALMACENAR LA DISPONIBILIDAD DE HORARIO DEL DOCTOR

        foreach ($schedule_hours as $key => $schedule_hour) {
            if(sizeof($schedule_hour["children"]) > 0){
                $schedule_day = DoctorScheduleDay::create([
                    "user_id" => $user->id,
                    "day" => $schedule_hour["day_name"],
                ]);
    
                foreach ($schedule_hour["children"] as $children) {
                    DoctorScheduleJoinHour::create([
                        "doctor_schedule_day_id" => $schedule_day->id,
                        "doctor_schedule_hour_id" => $children["item"]["id"],
                    ]);
                }
            }
        }
        return response()->json([
            "message" => 200
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            "doctor" => UserResource::make($user), 
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $schedule_hours = json_decode($request->schedule_hours,1);
        
        $users_is_valid = User::where("id","<>",$id)->where("email",$request->email)->first();

        if($users_is_valid){
            return response()->json([
                "message" => 403,
                "message_text" => "EL USUARIO CON ESTE EMAIL YA EXISTE"
            ]);
        }

        $user = User::findOrFail($id);

        if($request->hasFile("imagen")){
            if($user->avatar){
                Storage::delete($user->avatar);
            }
            $path = Storage::putFile("staffs",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->password){
            $request->request->add(["password" => bcrypt($request->password)]);
        }

        $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);

        $request->request->add(["birth_date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);

        // $request->request->add(["birth_date" => Carbon::parse($request->birth_date, 'GMT')->format("Y-m-d h:i:s")]);
        $user->update($request->all());

        if($request->role_id != $user->roles()->first()->id){
            $role_old = Role::findOrFail($user->roles()->first()->id);
            $user->removeRole($role_old);
    
            $role_new = Role::findOrFail($request->role_id);
            $user->assignRole($role_new);
        }

        // ALMACENAR LA DISPONIBILIDAD DE HORARIO DEL DOCTOR
        foreach ($user->schedule_days as $key => $schedule_day) {
            $schedule_day->delete();
        }

        foreach ($schedule_hours as $key => $schedule_hour) {
            if(sizeof($schedule_hour["children"]) > 0){
                $schedule_day = DoctorScheduleDay::create([
                    "user_id" => $user->id,
                    "day" => $schedule_hour["day_name"],
                ]);
    
                foreach ($schedule_hour["children"] as $children) {
                    DoctorScheduleJoinHour::create([
                        "doctor_schedule_day_id" => $schedule_day->id,
                        "doctor_schedule_hour_id" => $children["item"]["id"],
                    ]);
                }
            }
        }
        return response()->json([
            "message" => 200
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            "message" => 200
        ]);
    }
}
