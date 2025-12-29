<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Project Board</title>
    
    <script>
        (function() {
            const appearance = localStorage.getItem('appearance');
            let isDark = false;
            
            if (appearance === 'dark') {
                isDark = true;
            } else if (appearance === 'light') {
                isDark = false;
            } else {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                isDark = mediaQuery.matches;
            }
            
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    @vite('resources/js/app.ts')
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>