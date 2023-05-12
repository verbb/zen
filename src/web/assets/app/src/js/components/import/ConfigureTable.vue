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

                                <template v-else-if="colIndex === 'element'">
                                    <div v-html="row.data.element"></div>
                                    <div class="element-small" v-html="row.data.parents"></div>
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
                                <configure-preview :id="row.data.id" :state="getPreviewState(item.value, rowIndex)" />
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
import ConfigurePreview from '@components/import/ConfigurePreview.vue';
import Icon from '@components/Icon.vue';

import addIcon from '@/js/svg/add.svg?raw';
import changeIcon from '@/js/svg/change.svg?raw';
import removeIcon from '@/js/svg/remove.svg?raw';

export default {
    name: 'ConfigureTable',

    components: {
        Icon,
        TransitionExpand,
        ConfigurePreview,
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

            if (summary) {
                if (summary.add) {
                    html.push(`<span class="zui-import-summary add">${addIcon}${summary.add}</span>`);
                }

                if (summary.change) {
                    html.push(`<span class="zui-import-summary change">${changeIcon}${summary.change}</span>`);
                }

                if (summary.remove) {
                    html.push(`<span class="zui-import-summary remove">${removeIcon}${summary.remove}</span>`);
                }
            }

            return html.join('');
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

.zui-import-table td.element-cell .element-small {
    font-size: 10px;
    color: var(--gray-300);
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


</style>
