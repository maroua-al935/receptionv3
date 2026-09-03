@extends('President.layouts.master')

@section('body')
    @include('partials.visit-info-modern', [
        'data' => $data,
        'title' => $data[0]->firstname . ' ' . $data[0]->lastname,
        'subtitle' => 'Details du visiteur et informations de passage antenne.',
        'backUrl' => route('i_visitors_ant'),
        'isAntenne' => true,
    ])
@endsection
