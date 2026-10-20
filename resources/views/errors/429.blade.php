@include('errors._page', [
    'code' => 429,
    'title' => 'Too many requests',
    'message' => 'You have sent several requests in a short time. Please wait a minute and try again.',
])
