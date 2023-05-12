<template>
    <div v-if="state" data-zui-import-compare>
        <div class="zui-import-detail">
            <div v-if="loading" class="zui-loading-pane">
                <div class="zui-loading"></div>
                <span>{{ t('zen', 'Loading preview...') }}</span>
            </div>

            <div v-else-if="error" class="zui-error-pane error">
                <span class="error" v-html="errorMessage"></span>
            </div>

            <template v-else-if="data">
                <div class="zui-import-detail-content">
                    <div class="zui-import-detail-heading">{{ t('zen', 'Current Content') }}</div>

                    <div v-html="data.old"></div>
                </div>

                <div class="zui-import-indicator">
                    <div class="zui-import-indicator-icon approved">
                        <icon name="arrow-circle" />
                    </div>
                </div>

                <div class="zui-import-detail-content">
                    <div class="zui-import-detail-heading">{{ t('zen', 'New Content') }}</div>

                    <div v-html="data.new"></div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import Icon from '@components/Icon.vue';

import { getErrorMessage } from '@utils/forms';

export default {
    name: 'ConfigurePreview',

    components: {
        Icon,
    },

    props: {
        state: {
            type: Boolean,
            default: false,
        },

        id: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            loading: true,
            error: false,
            errorMessage: '',
            data: {},
        };
    },

    watch: {
        state(newValue) {
            if (newValue) {
                this.fetchPreview();
            }
        },
    },

    methods: {
        fetchPreview() {
            this.error = false;
            this.loading = true;
            this.errorMessage = '';

            const data = {
                filename: this.$route.params.filename,
                id: this.id,
            };

            Craft.sendActionRequest('POST', 'zen/import/get-config-preview', { data })
                .then((response) => {
                    this.data = response.data;

                    this.$nextTick(() => {
                        this.bindEventListeners();
                    });
                })
                .catch((error) => {
                    this.error = true;

                    const info = getErrorMessage(error);
                    this.errorMessage = `${info.text}<br>${info.trace}`;
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        bindEventListeners() {
            setTimeout(() => {
                // Fix element select images not working without jQuery
                this.$el.querySelectorAll('.elementselect .elements').forEach((elements) => {
                    new Craft.ElementThumbLoader().load($(elements));
                });

                // Implement a synced tabs behaviour to make it easy to compare things side-by-side
                this.$el.querySelectorAll('[data-zui-tab-target]').forEach((element) => {
                    element.addEventListener('click', (e) => {
                        e.preventDefault();

                        const selector = e.target.getAttribute('data-zui-tab-target');
                        const $container = e.target.closest('.zui-import-detail');

                        $container.querySelectorAll('[data-zui-tab-target]').forEach((el) => {
                            if (el.getAttribute('data-zui-tab-target') === selector) {
                                el.classList.add('sel');
                            } else {
                                el.classList.remove('sel');
                            }
                        });

                        $container.querySelectorAll('[data-zui-tab-pane]').forEach((el) => {
                            if (el.getAttribute('data-zui-tab-pane') === selector) {
                                el.classList.remove('hidden');
                            } else {
                                el.classList.add('hidden');
                            }
                        });
                    });
                });
            }, 100);
        },
    },
};

</script>

<style lang="scss">

//
// Import Item Detail
//

.zui-import-table .detail-row td {
    padding: 0;
    border: 0;
}

.zui-import-detail {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
    border-bottom: 1px var(--gray-100) solid;

    .zui-loading-pane,
    .zui-error-pane {
        min-height: 100px;
    }
}

.zui-import-detail-content {
    word-wrap: break-word;
    background: #fff;
    border-radius: var(--large-border-radius);
    box-shadow: 0 0 0 1px #cdd8e4;
    box-sizing: border-box;
    padding: 16px;
    position: relative;
    flex: 1;
}

.zui-import-detail-heading {
    position: relative;
    padding: 8px;
    margin: -16px -16px 16px -16px;
    background: var(--gray-050);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--gray-350);
    border-radius: var(--large-border-radius) var(--large-border-radius) 0 0;
}

.detail-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-350);
}

.zui-import-detail-content .field > .status-badge {
    &.add {
        background-color: #17a34a;
    }

    &.change {
        background-color: #f59e0c;
    }

    &.remove {
        background-color: #ed4343;
    }

    body.ltr & {
        left: -17px !important;
    }

    body.rtl & {
        right: -17px !important;
    }
}


.zui-import-detail-content .field {
    div.checkbox.disabled+label,
    div.checkbox.disabled:before,
    input.checkbox:disabled+label {
        opacity: 1;
    }
}

.zui-import-indicator {
    position: relative;
    width: 2rem;
}

.zui-import-indicator-icon {
    width: 30px;
    height: 30px;
    display: block;
    color: var(--gray-200);
    position: relative;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    svg {
        fill: currentColor;
        width: 100%;
        height: 100%;
    }
}

.zui-import-detail-tabs {
    display: flex;
    flex: 1;
    flex-direction: row;
    overflow-x: auto;
    font-size: 13px;
    background-color: var(--gray-050);
    border-radius: var(--large-border-radius) var(--large-border-radius) 0 0;
    box-shadow: inset 0 -1px 0 0 rgb(154 165 177 / 25%);
    box-sizing: border-box;
    margin: -16px -16px 16px -16px;
    min-height: 38px;
    padding: 0 16px;

    button {
        align-items: center;
        color: var(--tab-label-color);
        display: flex;
        flex-direction: row;
        height: 38px;
        padding: 0 12px;
        position: relative;
        white-space: nowrap;
        border-radius: 3px 3px 0 0;
    }

    .sel {
        --highlight-color: var(--gray-500);
        --tab-label-color: var(--text-color);

        background-color: var(--white);
        box-shadow: inset 0 2px 0 var(--highlight-color),0 0 0 1px rgba(51,64,77,.1),0 2px 12px rgba(205,216,228,.5) !important;
        cursor: default;
        position: relative;
        z-index: 1;
    }

    .has-change {
        padding-right: 22px;
    }

    .has-change::after {
        content: '';
        position: absolute;
        width: 7px;
        height: 7px;
        top: 50%;
        right: 5px;
        background: #4e85ff;
        border-radius: 10px;
        transform: translate(-50%, -50%);
    }
}

</style>
