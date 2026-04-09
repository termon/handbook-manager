<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Blade Kit</title>
      <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
     
      @vite(['resources/css/app.css', 'resources/js/app.js'])
      @livewireStyles
   </head>

   <body class="min-h-screen flex items-center justify-center bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100" >       
      {{ $slot }}
      @livewireScripts   
   </body>
</html>
