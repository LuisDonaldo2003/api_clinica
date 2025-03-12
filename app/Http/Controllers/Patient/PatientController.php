<?php

namespace App\Http\Controllers\Patient;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Patient\PatientPerson;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\Patient\PatientCollection;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $patients = Patient::where(DB::raw("CONCAT(patients.name,' ',IFNULL(patients.surname,''),' ',patients.email)"),"like","%".$search."%")
                        ->orderBy("id","desc")
                        ->paginate(2);

        return response()->json([
            "total" => $patients->total(),
            "patients" => PatientCollection::make($patients),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $patient_is_valid = Patient::where("n_document",$request->n_document)->first();

        if($patient_is_valid){
            return response()->json([
                "message" => 403,
                "message_text" => "EL PACIENTE YA EXISTE"
            ]);
        }

        if($request->hasFile("imagen")){
            $path = Storage::putFile("patients",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        // "Fri Oct 08 1993 00:00:00 GMT-0500 (hora estándar de Perú)"
        // Eliminar la parte de la zona horaria (GMT-0500 y entre paréntesis)
        if($request->birth_date){
            $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
    
            $request->request->add(["birth_date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        }

        $patient = Patient::create($request->all());

        $request->request->add(["patient_id" => $patient->id]);
        PatientPerson::create($request->all());

        return response()->json([
            "message" => 200
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $patient = Patient::findOrFail($id);

        return response()->json([
            "patient" => PatientResource::make($patient), 
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $patient_is_valid = Patient::where("id","<>",$id)->where("n_document",$request->n_document)->first();

        if($patient_is_valid){
            return response()->json([
                "message" => 403,
                "message_text" => "EL PACIENTE YA EXISTE"
            ]);
        }

        $patient = Patient::findOrFail($id);

        if($request->hasFile("imagen")){
            if($patient->avatar){
                Storage::delete($patient->avatar);
            }
            $path = Storage::putFile("patients",$request->file("imagen"));
            $request->request->add(["avatar" => $path]);
        }

        if($request->birth_date){
            $date_clean = preg_replace('/\(.*\)|[A-Z]{3}-\d{4}/', '', $request->birth_date);
    
            $request->request->add(["birth_date" => Carbon::parse($date_clean)->format("Y-m-d h:i:s")]);
        }

        // $request->request->add(["birth_date" => Carbon::parse($request->birth_date, 'GMT')->format("Y-m-d h:i:s")]);
        $patient->update($request->all());

        if($patient->person){
            $patient->person->update($request->all());
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
        $patient = Patient::findOrFail($id);
        if($patient->avatar){
            Storage::delete($patient->avatar);
        }
        $patient->delete();
        return response()->json([
            "message" => 200
        ]);
    }
}
