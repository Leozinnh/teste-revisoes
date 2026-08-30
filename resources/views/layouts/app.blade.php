<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Necessário para o axios enviar o token CSRF nas requisições da API --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AutoCare</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">

    {{-- Aqui o Vue monta a aplicação inteira (sidebar + página atual) --}}
    <div id="app"></div>

</body>
</html>
