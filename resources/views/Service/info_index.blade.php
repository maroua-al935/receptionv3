@extends('Service.layouts.master')

@section('body')
    @include('partials.visit-info-modern', [
        'data' => $data,
        'title' => $data[0]->firstname . ' ' . $data[0]->lastname,
        'subtitle' => 'Details du visiteur et informations de passage.',
        'backUrl' => route('i_visitors'),
    ])
@endsection
