@extends('errors.layout')

@section('title', '419 — Session Expired')
@section('code', '419')
@section('heading', 'Session expired')
@section('message', 'Your session has expired. Please refresh the page and try again.')
@section('action_text', 'Refresh Page')
@section('action_url', 'javascript:location.reload()')
