@extends('layouts.master')
@section('title')
    Attendances
@endsection
@section('content')
    <div class="card mb-3">
        <div class="card-header bg-white">
            <div class="align-items-center row">
                <div class="col">
                    <h5 class="mb-0 font-weight-bolder"> Attendances</h5>
                </div>
                <div class="col">

                </div>
                <div class="col-auto">

                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover  mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th scope="col" class="pl-4">ID</th>
                        <th scope="col">Module</th>
                        <th scope="col">Academic Year</th>
                        <th scope="col">Sessions</th>
                        <th scope="col">Points</th>
                        <th scope="col">Percentage</th>
                        <th scope="col">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody>

                    <tr>
                        <span hidden>{{$id = $teachModules->firstItem()}}</span>
                    @foreach( $modules as $module)
                        <tr >
                            <th class="pl-4">{{$id++}}</th>
                            <td>{{$module->module->code}} {{$module->module->name}}</td>
                            <td><span class="{{($module->academic_year->status=='Active')? 'text-primary' : (($module->academic_year->status=='Planning')? 'text-dark':'text-secondary') }}"><i class="fas fa-check-circle"></i></span>{{$module->academic_year->name }}  </td>
                            <td>{{$module->total}}</td>
                            <td>{{$module->present}}/{{($module->absent+$module->present)}}</td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: {{($module->present == 0)? 0 : ($module->present/($module->present+$module->absent))*100}}%" aria-valuenow="{{($module->present == 0)? 0 : ($module->present/($module->present+$module->absent))*100}}" aria-valuemin="0" aria-valuemax="100">{{round(($module->present == 0)? 0 : ($module->present/($module->present+$module->absent))*100)}}%</div>
                                </div>
                            </td>
                            <td >
                                <a class="btn btn-sm btn-light" href="{{ route('attendance.manage',['mid'=>$module->module_id,'aid'=>$module->academic_year_id]) }}"><i class="fas fa-calendar-alt"></i> Sessions</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="pt-1 no-gutters row">
                <div class="col">
                    <span>{{$teachModules->firstItem()}} to {{$teachModules->lastItem()}} of  {{$teachModules->total()}}</span>
                </div>
                <div class="col-auto">
                    {{ $teachModules->links() }}
                </div>
                <div class="ml-3 col-auto">

                </div>
            </div>
        </div>
    </div>
@endsection
