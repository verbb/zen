<template>
    <div v-if="loading" class="zui-loading-pane">
        <div class="zui-loading-wrap">
            <icon name="icon-bg" />

            <vue-3-lottie :animation-data="loadingSvg" :height="200" :width="200" />
        </div>

        <h1>{{ t('zen', 'Comparing content...') }}</h1>
    </div>

    <div v-else-if="error" class="zui-error-pane error">
        <icon name="error-large" />

        <span class="error" v-html="errorMessage"></span>
    </div>

    <div v-else-if="!isEmpty">
        <div class="zui-import-stats">
            <configure-stat-widget :value="getValue(data, 'summary.add')" text="New Elements" />
            <configure-stat-widget :value="getValue(data, 'summary.change')" text="Changed Elements" />
            <configure-stat-widget :value="getValue(data, 'summary.delete')" text="Deleted Elements" />
            <configure-stat-widget :value="getValue(data, 'summary.restore')" text="Restored Elements" />
        </div>

        <hr>

        <div id="content-container">
            <div id="content" class="content-pane">
                <configure-tabs :data="getValue(data, 'elementData')" />
                <configure-table v-model:enabled-data="enabledData" :data="getValue(data, 'elementData')" />
            </div>
        </div>
    </div>

    <div v-else class="zui-empty-pane">
        <icon name="success-large" />

        <span>{{ t('zen', 'No content changes detected.') }}</span>
    </div>
</template>

<script>
import { get } from 'lodash-es';
import { Vue3Lottie } from 'vue3-lottie';
import 'vue3-lottie/dist/style.css';

import loadingSvg from '@/js/svg/ab-testing.json?raw';

import ConfigureStatWidget from '@components/import/ConfigureStatWidget.vue';
import ConfigureTabs from '@components/import/ConfigureTabs.vue';
import ConfigureTable from '@components/import/ConfigureTable.vue';
import Icon from '@components/Icon.vue';

import { getErrorMessage } from '@utils/forms';

export default {
    name: 'InputConfigure',

    components: {
        ConfigureStatWidget,
        ConfigureTabs,
        ConfigureTable,
        Icon,
        Vue3Lottie,
    },

    data() {
        return {
            loading: true,
            error: false,
            errorMessage: '',
            data: {},
            enabledData: {},
        };
    },

    computed: {
        loadingSvg() {
            return JSON.parse(loadingSvg);
        },

        isEmpty() {
            return !(this.data && this.getValue(this.data, 'elementData').length);
        },
    },

    created() {
        this.$store().setBreadcrumbs(this.$route, ['index', 'configure']);

        this.$events.on('saveButton:configure', () => {
            this.submitForm();
        });
    },

    mounted() {
        this.$nextTick(() => {
            setTimeout(() => {
                this.loadConfig();
            }, 1000);
        });
    },

    methods: {
        getValue(collection, key, fallback) {
            return get(collection, key, fallback);
        },

        loadConfig() {
            this.error = false;
            this.loading = true;
            this.errorMessage = '';

            const data = {
                filename: this.$route.params.filename,
            };

            Craft.sendActionRequest('POST', 'zen/import/get-config-data', { data })
                .then((response) => {
                    this.data = response.data;

                    if (!this.isEmpty) {
                        this.$store().setSaveButton(this.$route);
                    }
                })
                .catch((error) => {
                    this.error = true;

                    const info = getErrorMessage(error);
                    this.errorMessage = `<h1>${info.heading}</h1><br>${info.text}<br>${info.trace}`;
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        submitForm() {
            let excludedData = {};

            // Get all the **disabled** elements so we can exclude them from import
            Object.entries(this.enabledData).forEach(([key, values]) => {
                values.forEach((value, index) => {
                    if (value === false) {
                        if (!excludedData[key]) {
                            excludedData[key] = [];
                        }

                        excludedData[key].push(index);
                    }
                });
            });

            const filename = this.$route.params.filename;

            if (Object.keys(excludedData).length) {
                excludedData = btoa(JSON.stringify(excludedData));

                this.$router.push({ path: `/import/review/${filename}/${excludedData}` });
            } else {
                this.$router.push({ path: `/import/review/${filename}` });
            }
        },
    },
};

</script>

<style lang="scss">

//
// State
//

.zui-loading-pane,
.zui-error-pane,
.zui-empty-pane {
    margin: auto;
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    min-height: 300px;
    width: 100%;
    border-radius: 8px;
    text-align: center;

    h1 {
        font-size: 24px;
        margin: 0;
        font-weight: 300;
        margin-top: 1rem;
    }

    svg {
        width: 16rem;
        height: 10rem;
        margin-bottom: 0.5rem;
    }
}

.zui-loading-pane {
    color: #0a9ea5;

    .zui-loading-wrap {
        width: 16rem;
        height: 10rem;
        position: relative;

        & > svg {
            position: absolute;
        }
    }

    .lottie-animation-container {
        margin-top: -1rem;
        position: relative;
        width: 10rem;

        svg path {
            stroke-width: 12px;
        }

        svg > g > g > g > path {
            fill-opacity: 1;
            fill: #f1f6fb;
        }
    }
}

.zui-error-pane {
    color: #dc2626;

    .error {
        color: #dc2626 !important;
    }
}

.zui-empty-pane {
    color: #17a34a;
}


//
// Stats Widgets
//

.zui-import-stats {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -16px 16px;
}

</style>
