@props(['action', 'arguments' => []])
@php($renderAction = $arguments ? $action($arguments) : $action)
@if($renderAction->isVisible())
    {{ $renderAction }}
@endif
