@extends('layouts.app')
@section('title', 'New post')

@section('content')
<div class="page-head">
    <div>
        <h1>New post</h1>
        <p class="sub">Write or generate a caption, pick a time and set a reminder.</p>
    </div>
</div>

<form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
    @csrf
    @include('posts._form')
</form>
@endsection
