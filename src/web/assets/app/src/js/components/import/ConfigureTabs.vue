<template>
    <header id="content-header" class="pane-header">
        <div id="tabs" class="pane-tabs">
            <div class="scrollable" role="tablist" :aria-label="t('app', 'Elements')">
                <a
                    v-for="(item, index) in data"
                    :id="'tab-' + index"
                    :key="item.value"
                    :href="'#' + item.value"
                    role="tab"
                    :data-id="index"
                    :aria-controls="index"
                    :tabindex="isFirst(index, data) ? '0' : '-1'"
                    :class="{ 'sel': isFirst(index, data) }"
                    :aria-selected="isFirst(index, data) ? 'true' : 'false'"
                >
                    <span class="tab-label">
                        {{ item.label }}
                    </span>
                </a>
            </div>

            <button
                type="button"
                class="btn menubtn hidden"
                :title="t('app', 'List all tabs')"
                :aria-label="t('app', 'List all tabs')"
                role="combobox"
                aria-haspopup="listbox"
                aria-expanded="false"
            ></button>

            <div class="menu">
                <ul class="padded" role="group">
                    <li
                        v-for="(item, index) in data"
                        :id="'aria-option-' + (index + 1)"
                        :key="item.value"
                        role="option"
                        :aria-selected="isFirst(index, data) ? 'true' : 'false'"
                    >
                        <a
                            :id="'option-' + (index + 1)"
                            :href="'#' + item.value"
                            :data-id="index"
                            tabindex="-1"
                            :class="{ 'sel': isFirst(index, data) }"
                        >
                            {{ item.label }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</template>

<script>

export default {
    name: 'ConfigureTabs',

    props: {
        data: {
            type: Array,
            default: () => { return []; },
        },
    },

    mounted() {
        this.$nextTick(() => {
            // Manually init Craft's tabs, which are rendered after jQuery initializes them
            Craft.cp.initTabs();
        });
    },

    methods: {
        isFirst(index, object) {
            return index === 0;
        },

        isLast(index, object) {
            return index == object.length - 1;
        },
    },
};

</script>
