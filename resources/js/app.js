import './bootstrap';
import '../css/app.css';
import '../css/auth.css';

import '../../public/vendor/summernote-0.8.18-dist/summernote-lite.min.js'
import '../../public/vendor/jquery/datatables'
import '../../public/vendor/jquery/datatables-bootstrap5.css'
import '../../public/vendor/jquery/datatables-bootstrap5'
import JHeaderTitle from '@/Components/JHeaderTitle.vue'
import JSpinner from '@/Components/JSpinner.vue'
import JEmptyState from '@/Components/JEmptyState.vue'

import { createApp, h } from 'vue';
import { Head, createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { cleanupStrayBootstrapModalArtifacts } from '@/Helpers/JModal';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

router.on('finish', () => {
    cleanupStrayBootstrapModalArtifacts();
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('JHeaderTitle', JHeaderTitle)
            .component('JEmptyState', JEmptyState)
            .component('Head', Head)
            .component('JSpinner', JSpinner);

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
