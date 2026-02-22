@extends('errors.layout')

@section('title', '403 — Access Denied')
@section('code', '403')
@section('heading', 'Access denied')
@section('message', $exception->getMessage() ?: 'You don't have permission to access this resource.')
@section('action_text', 'Back to Dashboard')
@section('action_url', '/')
