import preset from '../../../../vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#209F59',
                secondary: '#00747B',
                accent: '#885029',
                background: '#C6E0CD',
                'background-dark': '#2F4858',
            }
        }
    }
}