<template>
    <div class="zui-import-table-wrap">
        <div v-for="(item, index) in data" :id="item.value" :key="item.value" :class="{ 'hidden': !isFirst(index, data) }">
            <table class="zui-import-table data fullwidth">
                <thead>
                    <tr>
                        <th v-if="!readOnly" class="checkbox-cell selectallcontainer">
                            <div
                                class="checkbox"
                                :class="checkboxAllClass(item.value)"
                                role="checkbox"
                                tabindex="0"
                                :aria-checked="checkboxAllAria(item.value)"
                                :aria-label="t('app', 'Select all')"
                                @click.prevent="toggleAllCheckbox(item.value)"
                            ></div>
                        </th>

                        <th v-for="(column, colIndex) in item.columns" :key="colIndex" scope="col">
                            {{ column }}
                        </th>

                        <th v-if="!readOnly" class="thin" scope="col"></th>
                    </tr>
                </thead>

                <template v-for="(row, rowIndex) in item.rows" :key="rowIndex">
                    <template v-if="row.error">
                        <tbody>
                            <tr>
                                <td :colspan="Object.keys(item.columns).length + 1">
                                    <span class="error">{{ t('zen', 'Error:') }} {{ row.errorMessage }}</span>
                                </td>

                                <td>
                                    <button type="button" class="expand-btn" :class="{ 'active': getErrorState(item.value, rowIndex) }" @click.prevent="toggleError(item.value, rowIndex)">
                                        {{ t('zen', 'Detail') }}

                                        <icon name="chevron-right" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>

                        <tbody class="detail-row">
                            <tr>
                                <td :colspan="Object.keys(item.columns).length + 2">
                                    <transition-expand>
                                        <div v-show="getErrorState(item.value, rowIndex)">
                                            <div class="zui-import-detail error" v-html="row.errorDetail"></div>
                                        </div>
                                    </transition-expand>
                                </td>
                            </tr>
                        </tbody>
                    </template>

                    <tbody v-else>
                        <tr>
                            <td v-if="!readOnly" class="checkbox-cell" :class="{ 'sel': checkboxes[item.value][rowIndex] }">
                                <div
                                    class="checkbox"
                                    :title="t('app', 'Select')"
                                    role="checkbox"
                                    :aria-label="t('app', 'Select')"
                                    @click.prevent="toggleCheckbox(item.value, rowIndex)"
                                ></div>
                            </td>

                            <td v-for="(column, colIndex) in item.columns" :key="colIndex" :class="colIndex + '-cell'">
                                <template v-if="colIndex === 'state'">
                                    <span class="zui-review-status-badge" :class="row.data[colIndex]">{{ row.data[colIndex] }}</span>
                                </template>

                                <template v-else-if="colIndex === 'summary'">
                                    <span class="zui-import-summaries" v-html="getSummaryHtml(row.data[colIndex])"></span>
                                </template>

                                <template v-else>
                                    <div v-html="row.data[colIndex]"></div>
                                </template>
                            </td>

                            <td v-if="!readOnly">
                                <button type="button" class="expand-btn" :class="{ 'active': getPreviewState(item.value, rowIndex) }" @click.prevent="togglePreview(item.value, rowIndex)">
                                    {{ t('zen', 'Preview') }}

                                    <icon name="chevron-right" />
                                </button>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-if="!readOnly" class="detail-row">
                        <tr>
                            <td :colspan="Object.keys(item.columns).length + 2">
                                <transition-expand>
                                    <div v-show="getPreviewState(item.value, rowIndex)" data-zui-import-compare>
                                        <div class="zui-import-detail">
                                            <div class="zui-import-detail-content">
                                                <div class="zui-import-detail-heading">{{ t('zen', 'Current Content') }}</div>

                                                <div v-html="getPreviewHtml(row, 'old')"></div>
                                            </div>

                                            <div class="zui-import-indicator">
                                                <div class="zui-import-indicator-icon approved">
                                                    <icon name="arrow-circle" />
                                                </div>
                                            </div>

                                            <div class="zui-import-detail-content">
                                                <div class="zui-import-detail-heading">{{ t('zen', 'New Content') }}</div>

                                                <div v-html="getPreviewHtml(row, 'new')"></div>
                                            </div>
                                        </div>
                                    </div>
                                </transition-expand>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>
    </div>
</template>

<script>
import TransitionExpand from '@components/TransitionExpand.vue';
import Icon from '@components/Icon.vue';

import addIcon from '@/js/svg/add.svg?raw';
import changeIcon from '@/js/svg/change.svg?raw';
import removeIcon from '@/js/svg/remove.svg?raw';

export default {
    name: 'ConfigureTable',

    components: {
        Icon,
        TransitionExpand,
    },

    props: {
        data: {
            type: Array,
            default: () => { return []; },
        },

        enabledData: {
            type: Object,
            default: () => { return {}; },
        },

        readOnly: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:enabledData'],

    data() {
        return {
            checkboxes: {},
            previews: {},
            errors: {},
        };
    },

    watch: {
        checkboxes: {
            handler(newValue) {
                this.$emit('update:enabledData', newValue);
            },
            deep: true,
        },
    },

    created() {
        this.data.forEach((item) => {
            this.checkboxes[item.value] = [];

            Object.values(item.rows).forEach((row, index) => {
                this.checkboxes[item.value][index] = true;
            });
        });
    },

    mounted() {
        this.$nextTick(() => {
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
        });
    },

    methods: {
        isFirst(index, object) {
            return index === 0;
        },

        isLast(index, object) {
            return index == object.length - 1;
        },

        checkboxAllClass(element) {
            const checkboxes = this.checkboxes[element];

            if (checkboxes) {
                if (checkboxes.every((item) => { return item === true; })) {
                    return 'checked';
                }

                if (checkboxes.some((item) => { return item === true; })) {
                    return 'indeterminate';
                }
            }

            return '';
        },

        checkboxAllAria(element) {
            const className = this.checkboxAllClass(element);

            if (className === 'checked') {
                return 'true';
            }

            if (className === 'indeterminate') {
                return 'mixed';
            }

            return 'false';
        },

        toggleAllCheckbox(element) {
            const className = this.checkboxAllClass(element);
            const value = className === 'checked' ? false : true;

            this.checkboxes[element].forEach((item, index) => {
                this.checkboxes[element][index] = value;
            });
        },

        toggleCheckbox(element, index) {
            this.checkboxes[element][index] = !this.checkboxes[element][index];
        },

        getErrorState(element, index) {
            const key = `${element}-${index}`;

            return this.errors[key];
        },

        toggleError(element, index) {
            const key = `${element}-${index}`;

            this.errors[key] = !this.errors[key];
        },

        getPreviewState(element, index) {
            const key = `${element}-${index}`;

            return this.previews[key];
        },

        togglePreview(element, index) {
            const key = `${element}-${index}`;

            this.previews[key] = !this.previews[key];
        },

        getSummaryHtml(summary) {
            const html = [];

            if (summary.add) {
                html.push(`<span class="zui-import-summary add">${addIcon}${summary.add}</span>`);
            }

            if (summary.change) {
                html.push(`<span class="zui-import-summary change">${changeIcon}${summary.change}</span>`);
            }

            if (summary.remove) {
                html.push(`<span class="zui-import-summary remove">${removeIcon}${summary.remove}</span>`);
            }

            return html.join('');
        },

        getPreviewHtml(row, type) {
            if (type === 'old') {
                return row.compare?.old;
            }

            return row.compare?.new;
        },
    },
};

</script>

<style lang="scss">

//
// Element Table
//

.zui-import-table-wrap {
    margin: -24px;
}

.zui-import-table thead td,
.zui-import-table thead th {
    background: transparent !important;
}

.zui-import-table th {
    border-bottom: 1px var(--gray-200) solid;
}

.zui-import-table td {
    border-bottom: 1px var(--gray-100) solid;
}

.zui-import-table tr:hover td {
    background: transparent !important;
}

.zui-import-table td.element-cell .element {
    padding-top: 0;
    padding-bottom: 0;
    margin-left: -7px;
}

.zui-review-status-badge {
    --status-color: 136, 136, 136;

    font-size: 10px;
    font-weight: 500;
    text-transform: uppercase;
    cursor: default;
    user-select: none;
    padding: 3px 6px;
    border-radius: 4px;
    border: 1px solid transparent;
    color: rgba(var(--status-color), 1);
    background: rgba(var(--status-color), 0.05);
    border-color: rgba(var(--status-color), 0.25);

    &.add {
        --status-color: 23, 163, 74;
    }

    &.change {
        --status-color: 245, 158, 12;
    }

    &.delete {
        --status-color: 237, 67, 67;
    }
}

.zui-import-summaries {
    display: flex;
    align-items: center;
}

.zui-import-summary {
    display: inline-flex;
    align-items: center;
    margin-right: 10px;

    &.add {
        --status-color: 23, 163, 74;
    }

    &.change {
        --status-color: 245, 158, 12;
    }

    &.remove {
        --status-color: 237, 67, 67;
    }

    svg {
        width: 10px;
        height: 10px;
        margin-right: 2px;
        color: rgba(var(--status-color), 1);
        fill: currentColor;
    }
}

.expand-btn {
    display: flex;
    align-items: center;
    border: 1px lighten(#3f4d5a, 40%) solid;
    border-radius: 4px;
    padding: 3px 10px;
    font-size: 0.9em;

    svg {
        width: 10px;
        height: 10px;
        margin-left: 5px;
    }

    &.active svg {
        transform: rotate(90deg);
    }
}


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
