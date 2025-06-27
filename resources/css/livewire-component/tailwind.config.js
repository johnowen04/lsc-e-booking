import preset from '../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './vendor/filament/**/*.blade.php',
        './app/Livewire/**/*.php',
        './resources/views/livewire/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/pages/**/*.blade.php',
    ],
}
