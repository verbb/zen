<template>
    <div v-for="(item, index) in items" :key="index">
        <div class="zui-checkbox-item" :style="{ '--level': item.level }" @click="toggleChildren(item)">
            <div class="checkbox" :class="getClass(item)"></div>
            <component :is="item.value == '*' ? 'strong' : 'span'">
                {{ t('site', '{label} ({count})', { label: item.label, count: formatNumber(item.count) }) }}
            </component>
        </div>

        <tree-checkboxes v-if="item.children" :items="item.children" />
    </div>
</template>

<script>

export default {
    name: 'TreeCheckboxes',

    props: {
        items: {
            type: Array,
            required: true,
        },
    },

    methods: {
        formatNumber(number, format) {
            return Craft.formatNumber(number, format);
        },

        getClass(item) {
            const classes = [];

            if (item.checked) {
                classes.push('checked');
            } else if (this.hasEnabledChild(item)) {
                if (this.hasEnabledChild(item, true)) {
                    classes.push('checked');

                    item.indeterminate = false;
                } else {
                    classes.push('indeterminate');

                    item.indeterminate = true;
                }
            } else {
                item.indeterminate = false;
            }

            return classes;
        },

        hasEnabledChild(obj, allOnly = false) {
            if (Array.isArray(obj)) {
                if (allOnly) {
                    return obj.every((item) => {
                        return this.hasEnabledChild(item, allOnly);
                    });
                }

                return obj.some((item) => {
                    return this.hasEnabledChild(item, allOnly);
                });
            } if (typeof obj === 'object' && obj !== null) {
                if (obj.checked === true) {
                    return true;
                }

                for (const key in obj) {
                    if (this.hasEnabledChild(obj[key], allOnly)) {
                        return true;
                    }
                }
            }

            return false;
        },

        toggleChildren(item, state) {
            let newState = (state !== undefined) ? state : !item.checked;

            if (item.children) {
                if (this.hasEnabledChild(item)) {
                    if (this.hasEnabledChild(item, true)) {
                        newState = false;
                    } else {
                        newState = true;
                    }
                }

                item.children.forEach((child) => {
                    this.toggleChildren(child, newState);
                });

            } else {
                item.checked = newState;
            }
        },
    },
};

</script>

<style lang="scss">

.zui-checkbox-item {
    width: 100%;
    display: inline-flex;
    align-items: center;
    padding: 7px 10px;
    cursor: pointer;

    padding-left: calc(((var(--level) - 1) * 20px) + 10px);

    .checkbox {
        padding-right: 7px;
    }

    &:hover {
        background-color: var(--gray-050);
    }
}

</style>
