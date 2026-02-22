@extends('errors.layout')

@section('title', '503 — Maintenance Mode')
@section('code', '503')
@section('heading', 'We\'ll be right back')
@section('message', $exception->getMessage() ?: 'StudAI Career is currently undergoing scheduled maintenance. We\'ll be back shortly.')
@section('action_text', 'Try Again')
@section('action_url', '/')
