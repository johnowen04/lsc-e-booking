import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/filament/admin/theme.css", // if you want Filament theme built too
                "resources/css/filament/customer/theme.css",
                "resources/css/livewire-component/theme.css",
            ],
            refresh: true,
        }),
    ],
});
