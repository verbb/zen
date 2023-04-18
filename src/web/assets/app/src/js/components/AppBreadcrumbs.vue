<template>
    <ul class="breadcrumb-list">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" :class="(breadcrumbs.length - 1) === index ? 'last' : ''">
            <a @click.prevent="onClick(crumb.value)">{{ t('zen', crumb.label) }}</a>
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

body.ltr .breadcrumb-list li.last:after {
    display: none;
}

</style>
