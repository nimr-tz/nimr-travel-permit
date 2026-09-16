@include('errors.layout', [
    'code' => $exception->getStatusCode(),
    'title' => __('errors.500.title'),
    'message' => __('errors.500.message'),
    'retry' => true,
])
