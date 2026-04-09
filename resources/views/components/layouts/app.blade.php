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

   <body class="text-sm">
      <!-- choose the layout style -->
      <x-ui::flash position="bottom-right" />
      <x-layouts.sidebar>  
         <!-- your page content here -->        
            {{ $slot }}            
      </x-layouts.sidebar>
    
      @livewireScripts
      
   </body>
</html>
