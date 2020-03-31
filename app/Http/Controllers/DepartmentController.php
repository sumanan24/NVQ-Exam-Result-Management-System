<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Department;
use Illuminate\Support\Facades\DB;
class DepartmentController extends Controller
{
    public function getDerpartments(){

        $departments = Department::orderBy('id','asc')->paginate(10);
        return view('academic.departments',['departments' =>$departments]);
    }

    public function postCreateDepartment(Request $request){
        $this->validate($request,
        ['d_name'=>'required|max:255']
        );
        $department = new Department();
        $department->name = $request['d_name'];
        $message = 'There was an error';
        if($department->save()){
           $message = 'Department successfully created';
        }
        return redirect()->route('departments')->with(['message'=>$message]);
    }

    public function postEditDepartment(Request $request){
        $this->validate($request,[
            'd_name'=>'required|max:255'
            ]);
            $department = Department::find($request['d_id']);
            $department->name = $request['d_name'];
            $department->update();
            return response()->json(['new_name' => $department->name], 200);
    }
    public function getDeleteDepartment($d_id){
        $post = Department::where('id',$d_id)->first();
        $post->delete();
        return redirect()->route('departments')->with(['message'=>'Successfully deleted!']);
    }
}