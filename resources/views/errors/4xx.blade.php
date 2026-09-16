@include('errors.layout', [
    'code' => $exception->getStatusCode(),
    'title' => __('errors.4xx.title'),
    'message' => __('errors.4xx.message'),
])
