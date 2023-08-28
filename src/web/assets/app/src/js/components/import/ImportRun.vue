<template>
    <div class="zui-status">
        <transition name="pane-fade" mode="out-in">
            <div v-if="error" class="content-pane">
                <div class="zui-error-pane error">
                    <icon name="error-large" />
                    <span class="error" v-html="errorMessage"></span><br>
                    <input type="button" class="btn big submit" :value="t('app', 'Try again')" @click.prevent="onRefresh">
                </div>
            </div>

            <div v-else class="content-pane">
                <icon class="zui-koi" name="koi" />

                <div class="zui-status-wrap">
                    <transition name="pane-fade" mode="out-in">
                        <div v-if="cancelled" class="zui-status-intro">
                            <h1 class="h1-cancelled">{{ t('zen', 'Import cancelled') }}</h1>
                            <div class="zui-intro-text">{{ t('zen', 'Your import has been cancelled.') }}</div>

                            <input type="button" class="btn" :value="t('zen', 'Go Back')" @click.prevent="onBack">
                        </div>

                        <div v-else-if="completed" class="zui-status-intro">
                            <h1 class="h1-complete">{{ t('zen', 'Import complete!') }}</h1>
                            <span v-html="successMessage"></span><br>
                        </div>

                        <div v-else class="zui-status-intro">
                            <h1>{{ t('zen', 'Sit back and relax') }}</h1>
                            <div class="zui-intro-text">{{ t('zen', 'Your content is being imported now.') }}</div>

                            <div class="loading-bar"></div>
                            <div class="zui-intro-text">{{ t('zen', stepMessage) }}</div>

                            <input type="button" class="btn" :value="t('zen', 'Cancel')" @click.prevent="onCancel">
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import Icon from '@components/Icon.vue';

import { getErrorMessage } from '@utils/forms';

export default {
    name: 'ImportRun',

    components: {
        Icon,
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            successMessage: '',
            stepMessage: 'Preparing to import elements...',
            progressBar: null,
            completed: false,
            cancelled: false,
            taskId: '',
            processingLog: [],
        };
    },

    created() {
        this.$store().setSaveButton(this.$route);
        this.$store().setBreadcrumbs(this.$route, ['index', 'configure', 'review', 'run']);

        // Allow us to resume an import task, or create a new one
        this.taskId = this.$route.query.taskId;

        if (!this.taskId) {
            this.taskId = Craft.randomString(10);
        }
    },

    mounted() {
        this.$nextTick(() => {
            this.progressBar = new Craft.ProgressBar(this.$el.querySelector('.loading-bar'));
            this.progressBar.showProgressBar();

            setTimeout(() => {
                this.runImport();
            }, 500);
        });
    },

    methods: {
        updateProgressBar() {
            new Craft.Zen.TaskProgress(this.taskId, (({ status, taskInfo }) => {
                // Store the process log at the component level, as it will be gone when completed
                if (taskInfo && taskInfo.processingLog && taskInfo.processingLog.length) {
                    this.processingLog = taskInfo.processingLog;
                }

                if (status === 'step') {
                    this.progressBar.setProgressPercentage(taskInfo.progress);

                    if (taskInfo.progressLabel) {
                        this.stepMessage = taskInfo.progressLabel;
                    }
                } else if (status === 'complete') {
                    this.progressBar.setProgressPercentage(100);

                    // // Check if there are any logs specific to processing each item
                    if (this.processingLog && this.processingLog.length) {
                        this.successMessage = `<div class="zui-log-table">${this.getLogInfo()}</div>`;
                    }

                    setTimeout(() => {
                        this.completed = true;
                    }, 300);
                } else if (status === 'error') {
                    this.error = true;

                    // Check for server-side error with the queue
                    if (taskInfo.error) {
                        taskInfo = taskInfo.error;
                    }

                    const info = getErrorMessage(taskInfo);

                    // Check if there are any logs specific to processing each item
                    if (this.processingLog && this.processingLog.length) {
                        info.text = `<div class="zui-log-table">${this.getLogInfo()}</div>`;
                    }

                    this.errorMessage = `<h1>${info.heading}</h1><br>${info.text}<br>${info.trace}`;
                }
            }));
        },

        getLogInfo() {
            let errorDetail = [];

            this.processingLog.forEach((log) => {
                const line = [];

                line.push(`${log.element.type}: “${log.element.label}” <small>(${log.element.uid})</small>`);

                if (!log.success) {
                    line.push(`<span class="error">  > ${log.error}</span>`);

                    if (log.trace) {
                        line.push(`<span class="error">  > <small>${log.trace}</small></span>`);
                    }
                } else {
                    line.push(`<span class="success">  > ${this.t('zen', 'Successfullly imported.')}</span>`);
                }

                errorDetail.push(line.join('<br>'));
            });

            errorDetail = errorDetail.join('<br><br>');

            return errorDetail;
        },

        runImport() {
            this.error = false;
            this.errorMessage = '';

            const data = {
                filename: this.$route.params.filename,
                elementsToExclude: this.$route.params.elementsToExclude,
                taskId: this.taskId,
            };

            Craft.sendActionRequest('POST', 'zen/import/run', { data })
                .then((response) => {
                    this.updateProgressBar();
                })
                .catch((error) => {
                    this.error = true;

                    const info = getErrorMessage(error);
                    this.errorMessage = `<h1>${info.heading}</h1><br>${info.text}<br>${info.trace}`;
                });
        },

        onCancel() {
            this.error = false;
            this.errorMessage = '';

            const data = {
                taskId: this.taskId,
            };

            Craft.sendActionRequest('POST', 'zen/queue/cancel', { data })
                .then((response) => {
                    this.cancelled = true;
                })
                .catch((error) => {
                    this.error = true;

                    const info = getErrorMessage(error);
                    this.errorMessage = `<h1>${info.heading}</h1><br>${info.text}<br>${info.trace}`;
                });
        },

        onBack() {
            const filename = this.$route.params.filename;
            const elementsToExclude = this.$route.params.elementsToExclude;

            if (elementsToExclude) {
                this.$router.push({ path: `/import/review/${filename}/${elementsToExclude}` });
            } else {
                this.$router.push({ path: `/import/review/${filename}` });
            }
        },

        onRefresh() {
            // Attach the current taskId so we can resume it on reload
            this.$router.replace({
                path: this.$route.path,
                query: { taskId: this.taskId },
            });

            // Reload the page just for easiness
            setTimeout(() => {
                this.$router.go(0);
            }, 50);
        },
    },

};

</script>

<style lang="scss">

.zui-koi {
    max-width: 500px;
    margin: auto;
    margin-top: -7rem;
}

.zui-status {
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    gap: 2rem;

    #content-container {
        display: flex;
    }

    .content-pane {
        margin-top: 5rem;
        max-width: 520px;
        width: 50%;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #cdd8e4, 0 25px 50px -12px rgb(193 204 216);
        text-align: center;
    }

    .zui-status-wrap h1 {
        color: #0a9ea5;
        font-size: 32px;
        margin: 0;
        font-weight: 300;
        margin-bottom: 0.5rem;

        &.h1-complete {
            padding: 1rem 0;
        }

        &.h1-cancelled {
            color: #dc2626;
        }
    }

    .zui-intro-text {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 2rem;
    }

    .loading-bar {
        padding-bottom: 2rem;
    }
}

.zui-log-table {
    text-align: left;
    color: #404d5b;
    white-space: pre-wrap;
    padding: 1rem 0;

    .success {
        color: #0a9ea5 !important;
    }
}

</style>
