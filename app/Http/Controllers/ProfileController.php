<?php

namespace App\Http\Controllers;

use App\Category;
use App\Course;
use App\Job;
use App\Like;
use App\Message;
use App\Profile;
use App\User;
use App\Watch;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function CvUploadPost(Request $request){
        $validator = Validator::make($request->all(),[
            'cv'=> 'required',

        ],
            [
                'cv.required'=>'you must choose Pdf file before sending',
            ]);
        if($validator->fails()){
            return redirect()->back()->withErrors($validator);
        }
        $fileName =   $_FILES['cv']['name'];
        $request->cv->move(public_path('cvStudent/_'.Auth::id()), $fileName);
        $id = Auth::id();
        if(Profile::updateOrCreate(['user_id' => $id],["cv" => $fileName])){
            return redirect()->back()->with('message', 'Cv add!!');
        }else{
            return redirect('/placement');
        }
    }
    public function getInput($item){
        return view("student.partials.input$item");
    }

    public function cvFormat(){

        $cvFile = Course::where('id',Auth::user()->course_id)->value('cvFormat');
       return view('student/cvFormat',compact('cvFile'));

    }
    public function show($id)
    {

        if($id != Auth::id()){
            return  redirect('/');
        }
        $catId = Profile::where('user_id',$id)->value('category_id');
        $profile['StudentCategory'] =$catId != null ? Profile::where('category_id',$catId)->get()->count(): 0;
        $profile['allJobCat'] = $catId != null ?  Job::where('category_id',$catId)->get()->count() : 0;
//        $profile['allStart'] = $catId != null ?

        $profile['watches'] = Watch::where('watched',$id)->get()->count();
        $profile['categories'] = Category::where('course_id',Auth::user()->course_id)->get()->toArray();
        $profile['profile'] = Profile::with('category')->where('user_id',$id)->get()->toArray();
        $course = User::with('course')->where('id',$id)->get()->toArray();
        $profile['courseName'] = $course[0]['course']['name'];
        $profile['studentCourse'] = User::where('course_id',Auth::user()->course_id)->get()->count();
        $profile['jobsCourse'] = Job::where('course_id',Auth::user()->course_id)->get()->count();
        $presentAll = Profile::where('user_id',$id)->get(['category_id','about_me', 'education', 'my_skills', 'links','work_experience','image'])->toArray();
        if (isset($presentAll[0])){
            foreach ($presentAll[0] as $key => $value){
                if($value != null){
                    $presents[$key] = $value;
                }
            }
            $profile['present'] = $presents ?? '';
        }
        return view('student.profileTest',$profile);
        $profile = [];
        $profile['name'] = User::find($id)->name;
        $profile['cat_name'] = Category::select('cat_name')->where('id',User::find($id)->profile()->value('category_id'))->value('cat_name');
        $profile['categories'] = Category::where('course_id',Auth::user()->course_id)->get()->toArray();
        $profile['allData'] = Profile::where('user_id',$id)->get();
        $presentAll = Profile::where('user_id',$id)->get(['category_id','about_me', 'education', 'my_skills', 'links','work_experience','image'])->toArray();
        if (isset($presentAll[0])){
            foreach ($presentAll[0] as $key => $value){
                if($value != null){
                    $present[$key] = $value;
                }
            }
         $profile['present'] = $present;
        }

        $profile['newMatches'] = Message::where('user_id',Auth::id())->whereNotNull('student_id')->where('read',false)->get()->toArray();
        return view('student.profile',$profile);
    }
    public function update(Request $request){
        $id = json_decode($request->id);
        $col = $request->item;
        $val = $col == 'category_id' ? json_decode($request->value): $request->value;

        if(Profile::updateOrCreate(['user_id' => $id],["$col" => $val])){
            return response('success update', 201)->header('Content-Type', 'text/plain');
        }else{
            return response('something failed', 500)->header('Content-Type', 'text/plain');
        }

//        $col = $request->item;
//        $val = $col == 'category_id' ? json_encode($request->value): $request->value;
//        $id = json_decode($request->id);
//        if(Profile::updateOrCreate(['user_id' => $id],["$col" => $val])){
//            return response('success update', 201)->header('Content-Type', 'text/plain');
//        }else{
//            return response('something failed', 500)->header('Content-Type', 'text/plain');
//        }
    }


    public function imageUploadPost(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $fileName =   $_FILES['image']['name'];
        $request->image->move(public_path('images/_'.Auth::id()), $fileName);
        $col = $request->item;
        $id = json_decode($request->id);
        if(Profile::updateOrCreate(['user_id' => $id],["image" => $fileName])){
            return redirect('profile/'.Auth::id());
        }else{
            return redirect('welcome');
        }

    }
    public function reset(Request $request){
        $id = json_decode($request->id);
        $reset = Profile::where('user_id', $id)->update(['category_id'=>null,'about_me'=>null,'education'=>null,
            'my_skills'=>null,'links'=>null,'work_experience'=>null,'image'=>null,'confirm'=>0]);
        if($reset){
          return response('success reset', 201)->header('Content-Type', 'text/plain');
        }else{
            return response('something failed', 500)->header('Content-Type', 'text/plain');
        }
    }
public function showWizardProfile($id){
        return view('student.buildProfile');
}
}
