<template>
    <router-view v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
            <component :is="Component" :key="$route.path" />
        </transition>
    </router-view>
</template>

<script>

export default {
    name: 'App',

    created() {
        this.$router.beforeEach((to, from) => {
            // Reset the save button on route change. Up to each component to handle when to show
            this.$store().setSaveButton(null);

            this.$store().setRoute(null);

            // Update the document title
            document.title = `${to.meta.title} - ${this.$root.$options.systemName}`;
        });

        this.$router.afterEach((to, from) => {
            this.$store().setRoute(to);
        });
    },
};

</script>

<style lang="scss">

.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 0.5s;
}

.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
}

.pane-fade-enter-active,
.pane-fade-leave-active {
    transition: opacity 0.2s;
}

.pane-fade-enter-from,
.pane-fade-leave-to {
    opacity: 0;
}

</style>
