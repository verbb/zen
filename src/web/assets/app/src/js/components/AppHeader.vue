<template>
    <div id="page-title" class="flex">
        <h1 class="screen-title" :title="breadcrumb">{{ breadcrumb }}</h1>

        &nbsp;&nbsp;
        <a @click.prevent="navigate('/')">Index</a>&nbsp;&nbsp;&nbsp;&nbsp;
        <a @click.prevent="navigate('/import/configure/zen-import-230322_123909.zip')">Configure</a>&nbsp;&nbsp;&nbsp;&nbsp;
        <a @click.prevent="navigate('/import/review/zen-import-230322_123909.zip')">Review</a>&nbsp;&nbsp;&nbsp;&nbsp;
        <a @click.prevent="navigate('/import/run/zen-import-230322_123909.zip')">Run</a>
    </div>

    <transition name="page-fade" mode="out-in">
        <div v-if="saveButton" id="action-buttons" class="flex">
            <div class="btngroup">
                <button type="submit" class="btn submit" @click.prevent="onClick">{{ t('zen', saveButton) }}</button>
            </div>
        </div>
    </transition>
</template>

<script>

export default {
    name: 'AppHeader',

    computed: {
        saveButton() {
            return this.$store().$state.saveButton;
        },

        route() {
            return this.$store().$state.route;
        },

        breadcrumb() {
            const breadcrumbs = this.$store().$state.breadcrumbs;
            const first = breadcrumbs[0];
            const last = breadcrumbs[breadcrumbs.length - 1];

            if (!first || !last) {
                return '';
            }

            if (first.label === last.label) {
                return Craft.t('zen', '{page1}', { page1: first.label });
            }

            return Craft.t('zen', '{page1} — {page2}', { page1: first.label, page2: last.label });
        },
    },

    methods: {
        onClick() {
            this.$events.emit(`saveButton:${this.$store().$state.route.name}`);
        },

        navigate(path) {
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
