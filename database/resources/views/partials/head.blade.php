<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#10120f">
<meta name="color-scheme" content="dark">
<meta name="description" content="GiziSnap: foto makananmu, lihat perkiraan kalori dan gizinya, lalu bandingkan dengan target harianmu sendiri.">
<title>{{ isset($title) && $title ? $title.' — ' : '' }}{{ config('app.name', 'GiziSnap') }}</title>
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<script>document.documentElement.classList.add('js')</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
