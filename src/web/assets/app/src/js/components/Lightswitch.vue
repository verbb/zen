<template>
    <button type="button" class="lightswitch" :class="proxyValue ? 'on' : ''" role="switch" @click="toggle">
        <div class="lightswitch-container">
            <div class="handle"></div>
        </div>

        <input :value="proxyValue ? '1' : ''" type="hidden" :name="name">
    </button>
</template>

<script>

export default {
    name: 'Lightswitch',

    props: {
        name: {
            type: String,
            required: true,
        },

        modelValue: {
            type: Boolean,
            required: true,
        },
    },

    emits: ['update:modelValue'],

    computed: {
        proxyValue: {
            get() {
                return this.modelValue;
            },

            set(value) {
                this.$emit('update:modelValue', value);
            },
        },
    },

    methods: {
        toggle() {
            this.proxyValue = !this.proxyValue;
        },
    },
};

</script>
