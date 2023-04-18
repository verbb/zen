<template>
    <form ref="form" method="post" accept-charset="UTF-8" class="content-pane" enctype="multipart/form-data" @submit="submit">
        <div class="zui-start-wrap">
            <div class="zui-icon">
                <icon :name="icon" />
            </div>

            <div class="zui-start-intro">
                <div class="zui-start-header">
                    <slot name="header"></slot>
                </div>

                <div class="zui-start-body">
                    <div class="field-wrap">
                        <slot name="body"></slot>

                        <p v-if="error" class="error" v-html="errorMessage"></p>
                    </div>
                </div>

                <div class="zui-start-footer">
                    <button type="submit" class="btn big submit" :class="loading ? 'loading' : ''">
                        <span class="label">{{ t('zen', buttonText) }}</span>
                        <div class="spinner spinner-absolute"></div>
                    </button>
                </div>
            </div>
        </div>
    </form>
</template>

<script>
import { get } from 'lodash-es';

import Icon from '@components/Icon.vue';

import { getErrorMessage } from '@utils/forms';

export default {
    name: 'StartForm',

    components: {
        Icon,
    },

    props: {
        action: {
            type: String,
            required: true,
            default: '',
        },

        icon: {
            type: String,
            required: true,
            default: '',
        },

        buttonText: {
            type: String,
            required: true,
            default: '',
        },

        responseType: {
            type: String,
            default: 'json',
        },
    },

    emits: ['submit'],

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: false,
        };
    },

    methods: {
        submit(e) {
            e.preventDefault();

            this.loading = true;
            this.error = false;

            const options = {
                data: new FormData(this.$refs.form),
                responseType: this.responseType,
            };

            Craft.sendActionRequest('POST', this.action, options)
                .then((response) => {
                    this.$emit('submit', response);
                })
                .catch(async(error) => {
                    this.error = true;

                    // Because components can modify the `responseType`, it won't always be a JSON response for errors.
                    if (error.response.data.type === 'application/json') {
                        const jsonRawResponse = await error.response.data.text();

                        error.response.data = JSON.parse(jsonRawResponse);
                    }

                    const info = getErrorMessage(error);
                    this.errorMessage = `${info.text}<br><small>${info.trace}</small>`;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
};

</script>

<style lang="scss">

.zui-start {
    display: flex;
    justify-content: center;
    gap: 3rem;
    padding: 3rem;

    #content-container {
        display: flex;
    }

    .content-pane {
        max-width: 520px;
        width: 50%;
        border-radius: 8px;
        box-shadow: 0 0 0 1px #cdd8e4, 0 25px 50px -12px rgb(193 204 216);
        text-align: center;
    }

    .zui-icon {
        width: 5.5rem;
        height: 5.5rem;
        fill: #e2e8f0;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
    }

    .zui-start-wrap,
    .zui-start-intro {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .zui-start-body {
        flex: 1 1 auto;
    }

    h1 {
        color: #0a9ea5;
        font-size: 28px;
        margin: 0;
        font-weight: 300;
        margin-bottom: 0.5rem;
    }

    .zui-intro-text {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 2rem;
    }

    .field-wrap {
        padding: 1.5rem;
        border: 1px #e2e8f0 solid;
        border-radius: 8px;
        text-align: left;
        margin: 1rem 0;
    }

    .btn.big {
        margin: 1rem 0;
    }
}

</style>
