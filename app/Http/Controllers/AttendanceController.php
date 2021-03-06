<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AcademicYear;
use App\AttendanceSession;
use App\Module;
use App\Student;
use App\Attendance;
use App\Batch;
use App\Course;
use App\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function getTakeIndex($id)
    {
        $message = $warning = null;
        $session = AttendanceSession::where('id', $id)->first();
        if (!$session) {
            $warning = "Please check you data!";
            return redirect()->route('attendance.manage')->with(['message' => $message, 'warning' => $warning]);
        }

        $user = Auth::user();
        $employees = Employee::leftjoin('employee_module', 'employee_module.employee_id','=', 'employees.id')
            ->where([['module_id', $session->module_id], ['academic_year_id', $session->academic_year_id]])
            ->get();

        //check to login teacher to edit
        if($user->hasRole('Lecturer')){
            foreach( $employees as $employee){
                if($employee->employee_id != $user->profile_id){
                    return redirect()->back()->with(['warning'=>'You are not enrolled to this module!']);
                }
            }
        }
        $module = Module::where('id', $session->module_id)->first();
        $academic = AcademicYear::where('id', $session->academic_year_id)->first();
        if (!$module || !$academic) {
            $warning = "Please check you data!";
            return redirect()->route('attendance.manage')->with(['message' => $message, 'warning' => $warning]);
        }
        $students = Student::leftJoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
            ->select('student_id as id', "reg_no", "title", "fullname", "email", "nic")
            ->where([['course_id', $module->course_id], ['academic_year_id', $academic->id]])
            ->orderBy('reg_no', 'asc')
            ->get();

        return view('attendance.take', ['session' => $session, 'students' => $students, 'module' => $module, 'academic' => $academic]);
    }

    public function getTakeCreate(Request $request)
    {
        $message = $warning = null;
        $attendance_session_id = $request['session_id'];
        $students = array();
        $attendances = array();
        foreach ($request['take'] as $key => $value) {
            $students[] = $key;
        }
        foreach ($request['take'] as $value) {
            $attendances[] = $value[0];
        }
        $present=$absent = 0;
        foreach($attendances as $attend){
            if($attend == '1'){
                $present++;
            }
            else{
                $absent++;
            }
        }
        //update present details in session
        $attend_session = AttendanceSession::where('id',$attendance_session_id)->first();
        if(!$attend_session){
            $warning = "Attendance Session not exits!";
            return redirect()->back()->with(['message' => $message, 'warning' => $warning]);
        }
        $attend_session->present = $present;
        $attend_session->absent = $absent;
        if($attend_session->update()){
            $message .= 'Attendance Session record updated successfully ';
        }

        //save or update student record
        foreach ($students as $id) {
            $isUpdate = true;
            $attendance = Attendance::where([['attendance_session_id', $attendance_session_id],['student_id',$id]])->first();
            if(!$attendance){
                $attendance = new Attendance();
                $isUpdate = false;
            }
            $attendance->attendance_session_id = $attendance_session_id;
            $attendance->student_id = $id;
            $attendance->is_present = $request['take'][$id][0];

            if($isUpdate && $attendance->update()){
                $message = 'Attendance record updated successfully ';
            }else if(!$isUpdate && $attendance->save()){
                $message = 'Attendance recorded successfully ';
            }else {
                $warning = 'Attendance record not created. Try again!';
            }
        }
        return redirect()->back()->with(['message' => $message, 'warning' => $warning]);
        //return response()->json(['present' => $present, 'absent' => $absent, 'request' => $request['take']], 200);
    }
    public function getAttendancesIndex(){
        // $modules = DB::table('employee_module')
        //     ->select('employees.id as employee_id', 'employees.fullname as employee_fullname','employee_module.id', 'courses.name as course_name', 'modules.id as module_id', 'modules.course_id', 'modules.code as module_code', 'modules.name as module_name', 'academic_years.id as academic_year_id', 'academic_years.name as academic_year_name', 'academic_years.status as academic_year_status')
        //     ->leftJoin('academic_years', 'academic_years.id', '=', 'employee_module.academic_year_id')
        //     ->leftJoin('modules', 'modules.id', '=', 'employee_module.module_id')
        //     ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
        //     ->leftJoin('employees', 'employees.id', '=', 'employee_module.employee_id')
        //     ->orderBy('academic_years.name', 'desc')
        //     ->orderBy('module_code', 'asc')
        //     ->paginate(20);
        $courses = Course::orderBy('code', 'asc')->get();
        $modules = AttendanceSession::
                select('module_id','academic_year_id', DB::raw('count(id) as total'),DB::raw('sum(present) as present'), DB::raw('sum(absent) as absent'))
                ->groupBy('module_id')
                ->groupBy('academic_year_id')
                ->orderBy('academic_year_id', 'desc')
                ->orderBy('module_id', 'asc')
            ->paginate(20);
        //return response()->json(['emp' => $user_info, 'present'=> $user_info], 200);
        return view('attendance.index',['modules'=> $modules, 'courses'=> $courses]);

    }
    public function postAttendancesbyBatch(Request $request)
    {
        $message = $warning = null;
        $this->validate($request, [
            'batch_id' => 'required',
        ]);
        // $modules = DB::table('employee_module')
        //     ->select('employees.id as employee_id', 'employees.fullname as employee_fullname','employee_module.id', 'courses.name as course_name', 'modules.id as module_id', 'modules.course_id', 'modules.code as module_code', 'modules.name as module_name', 'academic_years.id as academic_year_id', 'academic_years.name as academic_year_name', 'academic_years.status as academic_year_status')
        //     ->leftJoin('academic_years', 'academic_years.id', '=', 'employee_module.academic_year_id')
        //     ->leftJoin('modules', 'modules.id', '=', 'employee_module.module_id')
        //     ->leftJoin('courses', 'courses.id', '=', 'modules.course_id')
        //     ->leftJoin('employees', 'employees.id', '=', 'employee_module.employee_id')
        //     ->orderBy('academic_years.name', 'desc')
        //     ->orderBy('module_code', 'asc')
        //     ->paginate(20);
        $batch = Batch::where('id',$request['batch_id'])->first();
        if(!$batch){
            $warning = "Batch not exits!";
            return redirect()->back()->with(['message' => $message, 'warning' => $warning]);
        }

        $courses = Course::orderBy('code', 'asc')->get();
        $modules = AttendanceSession::select('attendance_sessions.module_id', 'attendance_sessions.academic_year_id', DB::raw('count(attendance_sessions.id) as total'), DB::raw('sum(attendance_sessions.present) as present'), DB::raw('sum(attendance_sessions.absent) as absent'))
            ->leftJoin('modules', 'modules.id', '=', 'attendance_sessions.module_id')
            ->groupBy('module_id')
            ->groupBy('academic_year_id')
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('module_id', 'asc')
            ->where([['modules.course_id', $batch->course_id], ['attendance_sessions.academic_year_id', $batch->academic_year_id]])
            ->paginate(20);
        //return response()->json(['emp' => $user_info, 'present'=> $user_info], 200);
        //return redirect()->back()->with(['modules' => $modules, 'courses' => $courses]);
        return view('attendance.index', ['modules' => $modules, 'courses' => $courses]);
    }

    public function getReportIndex($mid,$aid){
        $message = $warning = null;
        $attendances = Attendance::select('student_id', DB::raw('count(attendances.id) as total'), DB::raw('sum(attendances.is_present) as present'))
            ->leftjoin('attendance_sessions', 'attendance_sessions.id','=', 'attendances.attendance_session_id')
            ->groupBy('attendances.student_id')
            ->orderBy('attendances.student_id', 'asc')
            ->where([['attendance_sessions.module_id',$mid],['attendance_sessions.academic_year_id',$aid]])
            ->get();

        $employees = Employee::leftjoin('employee_module', 'employee_module.employee_id', '=', 'employees.id')
            ->where([['module_id', $mid], ['academic_year_id', $aid]])
            ->get();

        $user = Auth::user();
        //check to login teacher to edit
        if($user->hasRole('Lecturer')){
            foreach( $employees as $employee){
                if($employee->employee_id != $user->profile_id){
                    return redirect()->back()->with(['warning'=>'You are not enrolled to this module!']);
                }
            }
        }

        $module = Module::where('id', $mid)->first();
        $academic = AcademicYear::where('id', $aid)->first();
        if (!$module || !$academic ||  !$employees) {
            $warning = "Please check you data!";
            return redirect()->back()->with(['message' => $message, 'warning' => $warning]);
        }
        //return response()->json(['attendances' => $attendances], 200);
        return view('attendance.report', ['attendances' => $attendances, 'module' => $module, 'academic' => $academic, 'employees' => $employees]);
    }

    public function getViewIndex($sid,$mid,$aid){
        $message = $warning = null;
        $employees = Employee::leftjoin('employee_module', 'employee_module.employee_id', '=', 'employees.id')
            ->where([['module_id', $mid], ['academic_year_id', $aid]])
            ->get();
        $module = Module::where('id', $mid)->first();
        $academic = AcademicYear::where('id', $aid)->first();
        $student = Student::where('id',$sid)->first();
        if (!$module || !$academic ||  !$employees || !$student) {
            $warning = "Please check you data!";
            return redirect()->back()->with(['message' => $message, 'warning' => $warning]);
        }

        $attendance = Attendance::select('student_id', DB::raw('count(attendances.id) as total'), DB::raw('sum(attendances.is_present) as present'))
            ->leftjoin('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
            ->groupBy('attendances.student_id')
            ->where([
            ['attendances.student_id', $sid],
            ['attendance_sessions.module_id', $mid],
            ['attendance_sessions.academic_year_id', $aid]
        ])
            ->first();

        $logs = Attendance::
                            leftjoin('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
                            ->where([
                                ['attendances.student_id',$sid],
                                ['attendance_sessions.module_id', $mid],
                                ['attendance_sessions.academic_year_id',$aid]])
                            ->paginate(20);
        //return response()->json(['logs' => $attendance], 200);
        return view('attendance.view', ['attendance'=> $attendance,'logs'=> $logs,'student'=> $student,'module' => $module, 'academic' => $academic, 'employees' => $employees]);

    }

    public function getStudentAttendancesIndex(){
        $student = Auth::user();
        if(!$student){
            return null;
        }
        $logs = AttendanceSession::select('attendance_sessions.module_id', 'attendance_sessions.academic_year_id', DB::raw('count(attendances.id) as total'), DB::raw('sum(attendances.is_present) as present'))
            ->leftJoin('attendances', 'attendances.attendance_session_id', '=', 'attendance_sessions.id')
            ->groupBy('module_id')
            ->groupBy('academic_year_id')
            ->where('student_id',$student->profile_id)
            ->paginate(20);

        $logs_new = AttendanceSession::select('attendance_sessions.module_id', 'attendance_sessions.academic_year_id', DB::raw('count(attendances.id) as total'), DB::raw('sum(attendances.is_present) as present'))
            ->leftJoin('attendances', 'attendances.attendance_session_id', '=', 'attendance_sessions.id')
            ->groupBy('module_id')
            ->groupBy('academic_year_id')
            ->where('student_id',$student->profile_id)
            ->get();
        $datas = array();
        $labels = array();

        foreach ($logs_new as $log){
            $datas[] = round(($log->present/$log->total)*100);
            $labels[]=$log->module->name;
        }

       // return response()->json(['logs' => $logs], 200);
        return view('attendance.index_student', ['logs' => $logs,'datas'=>json_encode($datas),
            'labels'=>json_encode($labels)]);
    }
    public function getStudentViewIndex($sid, $mid, $aid){
        $student = Auth::user();
        if(!$student){
            return null;
        }
        return $this->getViewIndex($student->profile_id, $mid, $aid);
    }
    public function getLecturerAttendancesIndex(){
        $courses = Course::orderBy('code', 'asc')->get();
        $lecturer = Auth::user();
        if(!$lecturer){
            return redirect()->back()->with(['warning'=>'Lecturer data is not available!']);
        }
        $lecturer_id = $lecturer->profile_id;
        $employee = Employee::where('id',$lecturer_id)->first();
        $teachModules = $employee->teachModules($employee->id);

        $modules =array();
        foreach ($teachModules as $teach){
            $modules []= AttendanceSession::
            select('module_id','academic_year_id', DB::raw('count(id) as total'),DB::raw('sum(present) as present'), DB::raw('sum(absent) as absent'))
                ->where([['module_id',$teach->modules_id],['academic_year_id',$teach->academic_year_id]])
                ->groupBy('module_id')
                ->groupBy('academic_year_id')
                ->orderBy('academic_year_id', 'desc')
                ->orderBy('module_id', 'asc')
                ->first();
        }
        //return response()->json(['teach'=>$teachModules,'modules'=> $modules], 200);
        return view('attendance.index_lecturer',['modules'=> $modules, 'courses'=> $courses,'teachModules'=>$teachModules]);
        // return response()->json(['logs' => $logs], 200);
    }
}
