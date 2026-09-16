@php
    // Show a reason only when the code gave one on purpose (e.g. a deactivated
    // account); the framework's generic policy text adds nothing.
    $reason = $exception->getMessage();
    if (in_array($reason, ['', 'This action is unauthorized.', 'Forbidden'], true)) {
        $reason = null;
    }
@endphp
@include('errors.layout', [
    'code' => 403,
    'title' => __('errors.403.title'),
    'message' => __('errors.403.message'),
    'detail' => $reason,
])
