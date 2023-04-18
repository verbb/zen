<template>
    <div>
        <div>
            <div class="zui-combo-field text" data-add-element-btn>
                <span class="zui-combo-field-summary">{{ getElementsSummary }}</span>

                <div v-if="loading" class="zui-loading"></div>
                <button type="button" class="btn">{{ t('zen', 'Edit') }}</button>
            </div>

            <div style="display: none;" data-elements-template>
                <div class="checkbox-select-wrap">
                    <fieldset class="checkbox-select">
                        <tree-checkboxes :items="items" />
                    </fieldset>
                </div>
            </div>
        </div>

        <input v-for="(item, index) in getCheckedItems(items)" :key="index" type="hidden" name="elements[]" :value="item">
    </div>
</template>

<script>
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light-border.css';

import TreeCheckboxes from '@components/export/TreeCheckboxes.vue';

export default {
    name: 'ElementCombobox',

    components: {
        TreeCheckboxes,
    },

    props: {
        items: {
            type: Array,
            default: () => { return []; },
        },

        loading: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        getElementsSummary() {
            const items = this.getCheckedItems(this.items);
            const labels = [];

            items.forEach((value) => {
                const item = this.findItem(this.items, value);

                if (item) {
                    labels.push(Craft.t('site', '{label} ({count})', { label: item.label, count: Craft.formatNumber(item.count) }));
                }
            });

            if (this.getInnerItems(this.items).length === items.length) {
                const item = this.findItem(this.items, '*');

                if (item) {
                    return Craft.t('site', '{label} ({count})', { label: item.label, count: Craft.formatNumber(item.count) });
                }
            } if (labels.length <= 2) {
                return labels.join(', ');
            } if (labels.length > 2) {
                return Craft.t('zen', '{label1}, {label2}, +{num} more', { label1: labels[0], label2: labels[1], num: labels.length - 2 });
            }

            return '';
        },
    },

    mounted() {
        this.$nextTick(() => {
            const $template = this.$el.querySelector('[data-elements-template]');

            if ($template) {
                $template.style.display = 'block';

                this.tippy = tippy(this.$el.querySelector('[data-add-element-btn]'), {
                    content: $template,
                    trigger: 'click',
                    allowHTML: true,
                    arrow: false,
                    interactive: true,
                    placement: 'bottom',
                    theme: 'light-border',
                    maxWidth: '1300px',
                    zIndex: 10,
                    offset: [0, -37],
                    hideOnClick: true,
                });
            }
        });
    },

    methods: {
        onClickItem(e) {
            e.currentTarget.querySelector('[type="checkbox"]').click();
        },

        formatNumber(number, format) {
            return Craft.formatNumber(number, format);
        },

        findItem(items, value) {
            for (let i = 0; i < items.length; i++) {
                const item = items[i];

                if (item.children) {
                    const childFound = this.findItem(item.children, value);

                    if (childFound) {
                        return childFound;
                    }
                }

                if (item.value === value) {
                    return item;
                }
            }
        },

        getCheckedItems(items) {
            const found = [];

            items.forEach((item) => {
                if (item.children) {
                    found.push(...this.getCheckedItems(item.children));
                } else if (item.checked) {
                    found.push(item.value);
                }
            });

            return found;
        },

        getInnerItems(items) {
            const found = [];

            items.forEach((item) => {
                if (item.children) {
                    found.push(...this.getInnerItems(item.children));
                } else {
                    found.push(item.value);
                }
            });

            return found;
        },
    },
};

</script>

<style lang="scss">

[data-tippy-root] {
    width: calc(100% + 2px);
}

.tippy-box[data-theme~=light-border] {
    background-color: #fff;
    background-clip: padding-box;
    border: none;
    color: #333;
    --shadow-color: rgba(205, 216, 228, 0.5);
    box-shadow: 0 0 0 1px #cdd8e4, 0 20px 25px -5px var(--shadow-color), 0 8px 10px -6px var(--shadow-color) !important;
    border-radius: 6px;
}

.tippy-content {
    padding: 0;
}

.checkbox-select-wrap {
    max-height: 500px;
    overflow: auto;
    border-radius: 6px;
}

.zui-combo-field {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;

    &:hover .btn {
        background-color: #d0d7e2;
    }

    .zui-loading {
        margin-left: auto;
        margin-top: 5px;
        margin-right: 1.5rem;
    }

    .zui-combo-field-summary {
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        max-width: 250px;
    }

    .btn {
        background: #e2e8f0;
        margin: -6px -9px;
        height: 100%;
        border-radius: 0 2px 2px 0;
    }
}

</style>
