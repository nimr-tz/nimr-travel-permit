@php
    $seconds = (int) ($exception->getHeaders()['Retry-After'] ?? 0);
@endphp
@include('errors.layout', [
    'code' => 429,
    'title' => __('errors.429.title'),
    'message' => __('errors.429.message'),
    'detail' => $seconds > 0 ? trans_choice('errors.429.wait', $seconds, ['seconds' => $seconds]) : null,
])
