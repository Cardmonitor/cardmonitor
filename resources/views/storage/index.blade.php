@extends('layouts.app')

@section('content')

    <h2>Lagerplätze</h2>
    <div class="alert alert-dark d-none d-sm-block mt-3" role="alert">
        Anzahl Artikel und Summe Verkaufspreis ist inklusive Unterlagerpläze.
    </div>
    <storage-table></storage-table>

@endsection