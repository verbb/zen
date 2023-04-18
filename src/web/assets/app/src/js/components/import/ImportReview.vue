<template>
    <div v-if="loading" class="zui-loading-pane">
        <div class="zui-loading-wrap">
            <icon name="icon-bg" />

            <vue-3-lottie :animation-data="loadingSvg" :height="200" :width="200" />
        </div>

        <h1>{{ t('zen', 'Preparing review...') }}</h1>
    </div>

    <div v-else-if="error" class="zui-error-pane error">
        <icon name="error-large" />

        <span class="error" v-html="errorMessage"></span>
    </div>

    <div v-else-if="data">
        <div id="content-container">
            <div class="zui-review">
                <div class="zui-review-wrap">
                    <div class="zui-review-intro">
                        <h1>{{ t('zen', 'Review your import') }}</h1>
                        <div class="zui-intro-text">{{ t('zen', 'Double check the elements you\'re about to import.') }}</div>
                    </div>
                </div>

                <div v-for="(item) in getValue(data, 'elementData')" :key="item.value" class="content-pane">
                    <configure-table :data="[item]" :read-only="true" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { get } from 'lodash-es';
import { Vue3Lottie } from 'vue3-lottie';
import 'vue3-lottie/dist/style.css';

import loadingSvg from '@/js/svg/work-list.json?raw';

import ConfigureTabs from '@components/import/ConfigureTabs.vue';
import ConfigureTable from '@components/import/ConfigureTable.vue';
import Icon from '@components/Icon.vue';

import { getErrorMessage } from '@utils/forms';

export default {
    name: 'InputReview',

    components: {
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
        };
    },

    computed: {
        loadingSvg() {
            return JSON.parse(loadingSvg);
        },
    },

    created() {
        this.$store().setBreadcrumbs(this.$route, ['index', 'configure', 'review']);

        this.$events.on('saveButton:review', () => {
            this.submitForm();
        });
    },

    mounted() {
        this.$nextTick(() => {
            setTimeout(() => {
                this.loadReview();
            }, 1000);
        });
    },

    methods: {
        getValue(collection, key, fallback) {
            return get(collection, key, fallback);
        },

        loadReview() {
            this.error = false;
            this.loading = true;
            this.errorMessage = '';

            const data = {
                filename: this.$route.params.filename,
                elementsToExclude: this.$route.params.elementsToExclude,
            };

            Craft.sendActionRequest('POST', 'zen/import/get-review-data', { data })
                .then((response) => {
                    this.data = response.data;

                    this.$store().setSaveButton(this.$route);
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
            const filename = this.$route.params.filename;
            const elementsToExclude = this.$route.params.elementsToExclude;

            if (elementsToExclude) {
                this.$router.push({ path: `/import/run/${filename}/${elementsToExclude}` });
            } else {
                this.$router.push({ path: `/import/run/${filename}` });
            }
        },
    },
};

</script>


<style lang="scss">

.zui-review {
    display: flex;
    justify-content: center;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
    margin: 1rem 0;

    .content-pane {
        width: 70%;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #cdd8e4, 0 25px 50px -12px rgb(193 204 216);
    }

    .zui-review-wrap {
        text-align: center;
    }

    .zui-review-wrap h1 {
        color: #0a9ea5;
        font-size: 32px;
        margin: 0;
        font-weight: 300;
        margin-bottom: 0.5rem;
    }

    .zui-review-text {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 2rem;
    }
}

</style>
