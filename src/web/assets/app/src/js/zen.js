// CSS needs to be imported here as it's treated as a module
import '@/scss/style.scss';

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept();
}

//
// Start Vue Apps
//

if (typeof Craft.Zen === typeof undefined) {
    Craft.Zen = {};
}

import { createVueApp } from './config';
import { createPinia } from 'pinia';

import App from '@components/App.vue';
import AppBreadcrumbs from '@components/AppBreadcrumbs.vue';
import AppHeader from '@components/AppHeader.vue';
import ElementCombobox from '@components/export/ElementCombobox.vue';

const pinia = createPinia();

Craft.Zen.App = Garnish.Base.extend({
    init(settings) {
        const app = createVueApp({
            components: {
                App,
            },

            ...settings,
        });

        app.component('ElementCombobox', ElementCombobox);

        // Attach Pinia (initialized once)
        app.use(pinia);

        app.mount('.zen-app');

        new Craft.Zen.Breadcrumbs(app, settings);
        new Craft.Zen.Header(app, settings);
    },
});

Craft.Zen.Breadcrumbs = Garnish.Base.extend({
    init(mainApp, settings) {
        const BreadcrumbsComponent = Object.assign(AppBreadcrumbs, settings);
        const app = createVueApp(BreadcrumbsComponent);

        // Attach Pinia (initialized once)
        app.use(pinia);

        // Pass in some state from the main app
        app.config.globalProperties.$events = mainApp.config.globalProperties.$events;
        app.config.globalProperties.$router = mainApp.config.globalProperties.$router;

        app.mount('#global-header #crumbs nav');
    },
});

Craft.Zen.Header = Garnish.Base.extend({
    init(mainApp, settings) {
        const HeaderComponent = Object.assign(AppHeader, settings);
        const app = createVueApp(HeaderComponent);

        // Attach Pinia (initialized once)
        app.use(pinia);

        // Pass in some state from the main app
        app.config.globalProperties.$events = mainApp.config.globalProperties.$events;
        app.config.globalProperties.$router = mainApp.config.globalProperties.$router;

        app.mount('#header-container #header');
    },
});

Craft.Zen.TaskProgress = Garnish.Base.extend({
    taskId: null,
    callback: null,
    pollInterval: null,

    init(taskId, callback) {
        this.taskId = taskId;
        this.callback = callback;

        // Trigger running the task - from JS so as not to lock the browser session
        Craft.sendActionRequest('POST', 'queue/run');

        // Poll the task job for updates
        this.pollInterval = setInterval(() => {
            this.updateTasks();
        }, 200);
    },

    updateTasks() {
        const data = {
            taskId: this.taskId,
        };

        Craft.sendActionRequest('POST', 'zen/queue/get-job-info?dontExtendSession=1', { data })
            .then((response) => {
                this.showTaskInfo(response.data.job);
            })
            .catch((error) => {
                clearInterval(this.pollInterval);

                this.onError(error);
            });
    },

    showTaskInfo(taskInfo) {
        if (taskInfo) {
            // 1 = waiting, 2 = reserved, 3 = done, 4 = failed
            if (taskInfo.status == 1 || taskInfo.status == 2) {
                this.onStep(taskInfo);
            } else if (taskInfo.status == 3) {
                this.onComplete(taskInfo);
            } else if (taskInfo.status == 4) {
                this.onError(taskInfo);
            }
        } else {
            this.onComplete(taskInfo);
        }
    },

    onStep(taskInfo) {
        this.callback({
            status: 'step',
            taskInfo,
        });
    },

    onError(taskInfo) {
        this.callback({
            status: 'error',
            taskInfo,
        });
    },

    onComplete(taskInfo) {
        clearInterval(this.pollInterval);

        this.callback({
            status: 'complete',
            taskInfo,
        });
    },
});
