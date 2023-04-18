import { createApp, reactive } from 'vue';
import mitt from 'mitt';
import { defineStore } from 'pinia';

// Vue plugins
import VueUniqueId from '@/js/vendor/vue-unique-id';
import { createRouter, createWebHistory } from 'vue-router';

import { clone } from '@utils/object';
import { t } from '@utils/translations';

import Start from '@components/start/Start.vue';
import ImportConfigure from '@components/import/ImportConfigure.vue';
import ImportReview from '@components/import/ImportReview.vue';
import ImportRun from '@components/import/ImportRun.vue';

// Allows us to create a Vue app with global properties and loading plugins
export const createVueApp = (props) => {
    const app = createApp({
        // Set the delimiters to not mess around with Twig
        delimiters: ['${', '}'],

        // Add in any props defined for _this_ instance of the app, like components
        // data, methods, etc.
        ...props,
    });

    // Fix Vue warnings
    app.config.unwrapInjectedRef = true;

    //
    // Plugins
    // Include any globally-available plugins for the app.
    // Be careful about adding too much here. You can always include them per-app.
    //

    // Vue Unique ID
    // Custom - waiting for https://github.com/berniegp/vue-unique-id
    app.use(VueUniqueId);


    // Vue Router
    // https://router.vuejs.org
    app.use(createRouter({
        history: createWebHistory(props.basePath),
        routes: [
            {
                name: 'index', path: '/', component: Start, meta: { title: 'Zen', saveButton: false },
            },
            {
                name: 'configure', path: '/import/configure/:filename', component: ImportConfigure, meta: { title: 'Configure Import', saveButton: 'Review Import' },
            },
            {
                name: 'review', path: '/import/review/:filename/:elementsToExclude?', component: ImportReview, meta: { title: 'Review Import', saveButton: 'Run Import' },
            },
            {
                name: 'run', path: '/import/run/:filename/:elementsToExclude?', component: ImportRun, meta: { title: 'Run Import', saveButton: false },
            },
        ],
    }));


    //
    // Global properties
    // Create global properties here, shared across multiple Vue apps.
    //

    // Provide `this.t()` for translations in SFCs.
    app.config.globalProperties.t = t;

    // Provide `this.clone()` for easy object cloning in SFCs.
    app.config.globalProperties.clone = clone;

    // Global events. Accessible via `this.$events` in SFCs.
    app.config.globalProperties.$events = mitt();

    // Create an app-wide store. Extract this out is getting too large
    app.config.globalProperties.$store = defineStore('app', {
        state: () => {
            return {
                breadcrumbs: [],
                route: [],
                saveButton: false,
            };
        },

        getters: {
            getBreadcrumbs: (state) => {
                return state.breadcrumbs;
            },

            getRoute: (state) => {
                return state.route;
            },

            getSaveButton: (state) => {
                return state.saveButton;
            },
        },

        actions: {
            setBreadcrumbs($route, values) {
                const routes = [];
                const allRoutes = app.config.globalProperties.$router.getRoutes();
                const params = $route.params;

                values.forEach((value) => {
                    const route = allRoutes.find((r) => {
                        return r.name === value;
                    });

                    if (route) {
                        const path = route.path.replace(':filename', params.filename);

                        routes.push({
                            label: route.meta.title,
                            value: path,
                        });
                    }
                });

                this.breadcrumbs = routes;
            },

            setRoute($route) {
                this.route = $route;
            },

            setSaveButton($route) {
                this.saveButton = $route?.meta?.saveButton;
            },
        },
    });

    // TODO: Try and figure out .env variables that aren't compiled
    app.config.globalProperties.$isDebug = !process.env.NODE_ENV || process.env.NODE_ENV === 'development';

    return app;
};
