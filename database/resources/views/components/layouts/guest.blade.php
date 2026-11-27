@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="min-h-dvh">
    {{ $slot }}
</body>
</html>
