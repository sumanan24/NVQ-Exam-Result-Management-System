<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\Batch;
use App\Course;
use App\Role;
use App\Student;
use App\StudentEnroll;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class StudentController extends Controller
{
    private $districts  = array('Ampara','Anuradhapura','Badulla','Batticaloa','Colombo','Galle','Gampaha','Hambantota','Jaffna','Kalutara','Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar','Matale','Matara','Monaragala','Mullaitivu','Nuwara Eliya','Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavuniya');
    private $statuses = array('Following', 'Completed','Droupout','Long Absent');
    private $modes = array('Full Time', 'Part Time','Short Time');
    private $provinces = array('Central','Eastern','North Central','North Western','Northern','Sabaragamuwa','Southern','Uva','Western');
    private $semesters = array('1'=>'Semester 1','2'=>'Semester 2');
    private $exams = array('T'=>'Theory','P'=>'Practical','B'=>'Theory and Practical');
    public function getStudents(){
        $students = Student::orderBy('reg_no','asc')->paginate(30);
        return view('student.students',['students'=>$students]);
    }
    public function getStudentsbyBatch($id){
        $batch = Batch::where('id',$id)->first();
        $students = Student::leftJoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
                    ->select('student_id as id',"reg_no","title","fullname","shortname","gender","civil_status","email","nic","date_birth","phone","address","zip","district","divisional","province","blood","emergency_name","emergency_address","emergency_phone","emergency_relationship")
                    ->where([['course_id',$batch->course_id],['academic_year_id',$batch->academic_year_id]])
                    ->orderBy('students.reg_no','asc')
                    ->paginate(30);
        return view('student.students',['students'=>$students]);
    }
    public function getStudentsbyCourse($id){
        $students = Student::leftJoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
                    ->select('student_id as id',"reg_no","title","fullname","shortname","gender","civil_status","email","nic","date_birth","phone","address","zip","district","divisional","province","blood","emergency_name","emergency_address","emergency_phone","emergency_relationship")
                    ->where('course_id',$id)
                    ->orderBy('students.reg_no','asc')
                    ->paginate(30);
        return view('student.students',['students'=>$students]);
    }
    public function getStudentsbyAcademicYear($id){
        $batch = Batch::where('id',$id)->first();
        $students = Student::leftJoin('student_enrolls', 'students.id', '=', 'student_enrolls.student_id')
                    ->select('student_id as id',"reg_no","title","fullname","shortname","gender","civil_status","email","nic","date_birth","phone","address","zip","district","divisional","province","blood","emergency_name","emergency_address","emergency_phone","emergency_relationship")
                    ->where('academic_year_id',$id)
                    ->orderBy('students.reg_no','asc')
                    ->paginate(30);
        return view('student.students',['students'=>$students]);
    }

    public function getStudentCreate(){
        $courses = Course::orderBy('name','asc')->get();
        $academicyears = AcademicYear::orderBy('name','desc')->get();

        return view('student.student',['provinces'=>$this->provinces,'statuses'=>$this->statuses,'modes'=>$this->modes,'districts'=>$this->districts,'courses'=>$courses,'academicyears'=>$academicyears]);
    }

    public function postCreateStudent(Request $request){
        $message = $warning = null;
        $isUpdate = false;
        $this->validate($request, [
            'reg_no' => 'required',
            'fullname' => 'required',
            'shortname' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'nic' => 'required',
            'date_birth' => 'required',
            'phone' => 'required',
            'academic_year_id' => 'required',
            'course_id' => 'required',
            'course_mode' => 'required',
            'enroll_date' => 'required',
            'status' => 'required'
        ]);
        $student = Student::where('id', $request['student_id'])->first();
        if($student){
            $isUpdate = true;
        }else{
            $student = new Student();
            $this->validate($request,[
                'email'=>'unique:students|unique:users',
                'reg_no'=>'unique:students',
                'nic'=>'unique:students'
            ]);
        }
        $Course = Course::find($request['course_id']);
        if(!$Course){
            return null;
        }
        $AcademicYear = AcademicYear::find($request['academic_year_id']);
        if(!$AcademicYear){
            return null;
        }

        $student->fullname = $request['fullname'];
        $student->reg_no = $request['reg_no'];
        $student->shortname = $request['shortname'];
        $student->gender = $request['gender'];
        $student->email = $request['email'];
        $student->nic = $request['nic'];
        $student->date_birth = $request['date_birth'];
        $student->phone = $request['phone'];

        $student->title = $request['title'];
        $student->civil_status = $request['civil'];
        $student->address = $request['address'];
        $student->zip = $request['zip'];
        $student->district = $request['district'];
        $student->divisional = $request['divisional'];
        $student->province = $request['province'];
        $student->blood = $request['blood'];
        $student->emergency_name = $request['emergency_name'];
        $student->emergency_address = $request['emergency_address'];
        $student->emergency_phone = $request['emergency_phone'];
        $student->emergency_relationship = $request['emergency_relationship'];

        //User data
        $role_admin = Role::where('name', 'Student')->first();
        $uns = explode('/', $student->reg_no);
        $un = null;
        foreach ($uns as $key){
            $un .= $key;
        }
        //end


        if($isUpdate){
            $message = $student->fullname . " Successfully Updated!";
            $student->update();
            //update user table
            $admin = User::where('profile_id',$student->id)->first();
            $admin->firstname = $student->reg_no;
            $admin->lastname = $student->shortname;
            $admin->username = strtolower($un);
            $admin->email = $student->email;
            $admin->update();

        }else{
            $message = $student->fullname . " Successfully Created!";
            $student->save();

            //create a user account for login
            $admin = new User();
            $admin->profile_id = $student->id;
            $admin->firstname = $student->reg_no;
            $admin->lastname = $student->shortname;
            $admin->username = strtolower($un);
            $admin->email = $student->email;
            $admin->password = bcrypt($student->nic);
            $admin->save();
            $admin->roles()->attach($role_admin);

        }

        foreach ($request['course_id'] as $index=>$course_id) {
            if($isUpdate){
                $enroll = StudentEnroll::where('id', $request['enroll_id'][$index])->first();
            }else{
                $enroll = new StudentEnroll();
            }
            $batch = Batch::where([['academic_year_id', $request['academic_year_id'][$index]],['course_id', $request['course_id'][$index]]])->first();
            $enroll->academic_year_id = $request['academic_year_id'][$index];
            $enroll->course_id = $request['course_id'][$index];
            $enroll->course_mode = $request['course_mode'][$index];
            $enroll->enroll_date = $request['enroll_date'][$index];
            $enroll->status = $request['status'][$index];
            $enroll->student_id = $student->id;
            if(!$batch){
                $warning = "Enrolled Course doesn't have batch name";
            }
            if($batch && $isUpdate){
                $enroll->update();
            }else if($batch && !$isUpdate){
                $enroll->save();
            }
        }
        return redirect()->route('students')->with(['message' => $message, 'warning' => $warning]);
    }
    public function getDeleteStudent($id){
        $message = $warning = null;
        $post1 = StudentEnroll::where('student_id',$id)->first();
        $post2 = Student::where('id',$id)->first();
        if($post1 && $post2){
            $warning = $post2->fullname." was not Deleted, Try Again!";
        }
        return redirect()->route('students')->with(['message' => $message, 'warning' => $warning]);
    }
    public function getEditStudent($id){
        $courses = Course::orderBy('name', 'asc')->get();
        $academicyears = AcademicYear::orderBy('name', 'desc')->get();
        $student = Student::where('id',$id)->first();
        $enrolls = StudentEnroll::where('student_id', $id)->get();
        return view('student.student', ['enrolls'=> $enrolls,'student'=> $student,'provinces' => $this->provinces, 'statuses' => $this->statuses, 'modes' => $this->modes, 'districts' => $this->districts, 'courses' => $courses, 'academicyears' => $academicyears]);

    }
    public function getEnrollIndex($id){
        $student = Student::where('id',$id)->first();
        if(!$student){
            return redirect()->back()->with('warning','Invalid Student Data !');
        }
        $courses = Course::orderBy('name','asc')->get();
        $academicyears = AcademicYear::orderBy('name','desc')->get();

        return view('student.enroll',['student'=>$student,'statuses'=>$this->statuses,'modes'=>$this->modes,'courses'=>$courses,'academicyears'=>$academicyears]);
    }
    public function postStudentEnroll(Request $request){
        $message = $warning = null;
        $this->validate($request, [
            'reg_no' => 'required',
            'academic_year_id' => 'required',
            'course_id' => 'required',
            'course_mode' => 'required',
            'enroll_date' => 'required',
            'status' => 'required'
        ]);
        $student = Student::where('id', $request['student_id'])->first();
        $enroll = new StudentEnroll();
        $batch = Batch::where([['academic_year_id', $request['academic_year_id']],['course_id', $request['course_id']]])->first();
        $enroll->academic_year_id = $request['academic_year_id'];
        $enroll->course_id = $request['course_id'];
        $enroll->course_mode = $request['course_mode'];
        $enroll->enroll_date = $request['enroll_date'];
        $enroll->status = $request['status'];

        $enroll->student_id = $student->id;
        $isEnrolled = StudentEnroll::where([['student_id',$student->id],['course_id',$request['course_id']]])->first();
        if($isEnrolled){
            return redirect()->back()->with('warning','Student Already Enrolled to the Course');
        }
        if(!$batch){
            return redirect()->back()->with('warning','Enrolled Course doesn\'t have Batch Name');
        }
        if($enroll->save()){
            $message = $student->fullname . " Successfully Enrolled ";
        }
        return redirect()->route('students')->with(['message' => $message, 'warning' => $warning]);
    }
    public function getCoursesIndex($id){
        $enrolls = StudentEnroll::where('student_id',$id)->get();
        return view('student.courses',['enrolls'=>$enrolls,'semesters'=>$this->semesters,'exams'=>$this->exams]);

    }
}
