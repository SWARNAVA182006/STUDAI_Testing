@extends('errors.layout')

@section('title', '429 — Too Many Requests')
@section('code', '429')
@section('heading', 'Slow down')
@section('message', 'You\'ve made too many requests. Please wait a moment and try again.')
@section('action_text', 'Go Back')
@section('action_url', 'javascript:history.back()')
