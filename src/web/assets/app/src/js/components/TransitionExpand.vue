<template>
    <Transition
        name="expand"
        @enter="onEnter"
        @after-enter="onAfterEnter"
        @leave="onLeave"
    >
        <slot></slot>
    </Transition>
</template>

<script>

export default {
    name: 'TransitionExpand',

    methods: {
        onEnter(element) {
            const width = getComputedStyle(element).width;

            element.style.width = width;
            element.style.position = 'absolute';
            element.style.visibility = 'hidden';
            element.style.height = 'auto';

            const height = getComputedStyle(element).height;

            element.style.width = null;
            element.style.position = null;
            element.style.visibility = null;
            element.style.height = 0;

            getComputedStyle(element).height;

            requestAnimationFrame(() => {
                element.style.height = height;
            });
        },

        onAfterEnter(element) {
            element.style.height = 'auto';
        },

        onLeave(element) {
            const height = getComputedStyle(element).height;

            element.style.height = height;

            getComputedStyle(element).height;

            requestAnimationFrame(() => {
                element.style.height = 0;
            });
        },
    },
};

</script>

<style scoped>

* {
    will-change: height;
    transform: translateZ(0);
    backface-visibility: hidden;
    perspective: 1000px;
}

</style>

<style lang="scss">

.expand-enter-active,
.expand-leave-active {
    transition: height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    height: 0;
    opacity: 0;
}

</style>
