@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('title_login') }}</div>

                <div class="card-body">
                    <a href="{{ $loginUrl }}" class="btn btn-success">{{ __('login') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
