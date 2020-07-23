<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use DataTables;
use App\Http\Requests\Department\AddDepartmentRequest;
use Carbon\Carbon;

class DepartmentAdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show department employee list with report details
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function employeeReports(Request $request){
        if ($request->ajax()) {
            $data = User::where('type',3)->where('department',auth()->user()->id)
                            ->whereHas('user_reports', function($q){
                                $q->where('status', 0);
                            })->has('user_reports')->latest()->get();
            return DataTables::of($data)
                    ->addIndexColumn()->editColumn('first_name', function($row){
                        return $row->first_name.' '.$row->last_name;
                    })->editColumn('status', function($row){
                        if($row->status){
                            return '<a href="javascript:void(0)" class="status btn btn-success btn-sm" data-id="'.$row->id.'">Active</a>';
                        }
                        return '<a href="javascript:void(0)" class="status btn btn-danger btn-sm" data-id="'.$row->id.'">Not active</a>';
                    })->editColumn('report_count', function($row){ 
                        return $row->user_reports->count();
                    })->editColumn('created_at', function($row){
                        if($row->user_reports->count() > 0){
                            return date('Y-m-d H:i:s', strtotime($row->created_at));
                        }
                        return '';
                    })->addColumn('action', function($row){
                            $url = URL('department/report/user').'/'.$row->id;
                            return '<a href="'.$url.'" class="edit btn btn-info btn-sm" >VIEW</a>';
                    })->rawColumns(['action'])->make(true);
        }
        return view('department.employees-reports');
    }

    /**
     * Show department employee submited report list
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function userReportList($id,Request $request){
        if ($request->ajax()) {

            $data = Report::where('user_id',$id)->where('department_id',auth()->user()->id)->latest()->get();
            return DataTables::of($data)
                    ->addIndexColumn()->editColumn('status', function($row){
                        if($row->status){
                            return 'Verified';
                        }
                        return 'Not Verified';
                    })->editColumn('created_at', function($row){
                        return date('Y-m-d H:i:s', strtotime($row->created_at) );
                    })->addColumn('week', function($row){ 
                        $now = Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at);
                        $start =  $now->startOfWeek()->addDay(0)->format('Y-m-d');
                        $end =  $now->startOfWeek()->addDay(6)->format('Y-m-d');
                        return $start .' to '.$end;
                    })->addColumn('action', function($row){
                        $url = URL('department/user/report').'/'.$row->id;
                        return '<a href="'.$url.'" class="edit btn btn-info btn-sm" >View</a>';
                    })->rawColumns(['action'])->make(true);
        }
        return view('department.employee-report-list');
    }
    
}
