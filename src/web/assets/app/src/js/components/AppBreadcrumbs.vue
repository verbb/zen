<template>
    <ul id="crumb-list" class="breadcrumb-list">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="crumb" :class="(breadcrumbs.length - 1) === index ? 'current' : ''">
            <button class="crumb-button" @click.prevent="onClick(crumb.value)">
                <span>{{ t('zen', crumb.label) }}</span>
            </button>
        </li>
    </ul>
</template>

<script>

export default {
    name: 'AppBreadcrumbs',

    computed: {
        breadcrumbs() {
            return this.$store().$state.breadcrumbs;
        },
    },

    methods: {
        onClick(path) {
            this.$router.push({ path });
        },
    },
};

</script>

<style lang="scss">

.crumb-button {
    color: var(--link-color);
    cursor: pointer;
}

</style>
