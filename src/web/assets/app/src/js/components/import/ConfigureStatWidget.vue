<template>
    <div class="zui-import-stat">
        <div class="zui-import-stat-number">{{ displayedAmount }}</div>
        <div class="zui-import-stat-heading">{{ t('zen', text) }}</div>
    </div>
</template>

<script>

export default {
    name: 'StatWidget',

    props: {
        value: {
            type: Number,
            required: true,
            default: 0,
        },

        text: {
            type: String,
            required: true,
            default: '',
        },
    },

    data() {
        return {
            timestamp: 0,
            startTimestamp: 0,
            currentStartAmount: 0,
            startAmount: 0,
            currentAmount: 0,
            duration: 800,
            currentDuration: 0,
            remaining: 0,
            animationFrame: 0,
            endAmount: this.value,
        };
    },

    computed: {
        isCountingUp() {
            return this.endAmount > this.startAmount;
        },

        displayedAmount() {
            return Craft.formatNumber(this.currentAmount);
        },
    },

    mounted() {
        this.currentDuration = this.duration;
        this.remaining = this.duration;
        this.animationFrame = window.requestAnimationFrame(this.counting);
    },

    methods: {
        counting(timestamp) {
            this.timestamp = timestamp;

            if (!this.startTimestamp) {
                this.startTimestamp = timestamp;
            }

            const progress = timestamp - this.startTimestamp;
            this.remaining = this.currentDuration - progress;

            if (!this.isCountingUp) {
                this.currentAmount = this.currentStartAmount - ((this.currentStartAmount - this.endAmount) * (progress / this.currentDuration));
                this.currentAmount = this.currentAmount < this.endAmount ? this.endAmount : this.currentAmount;
            } else {
                this.currentAmount = this.currentStartAmount + (this.endAmount - this.currentStartAmount) * (progress / this.currentDuration);
                this.currentAmount = this.currentAmount > this.endAmount ? this.endAmount : this.currentAmount;
            }

            if (progress < this.currentDuration) {
                this.animationFrame = window.requestAnimationFrame(this.counting);
            }
        },
    },
};

</script>

<style lang="scss">

.zui-import-stat {
    flex: 1 0 0%;
    width: 100%;
    max-width: 100%;
    word-wrap: break-word;
    background: #fff;
    border-radius: var(--large-border-radius);
    box-shadow: 0 0 0 1px #cdd8e4, 0 2px 12px rgb(205 216 228 / 50%);
    box-sizing: border-box;
    padding: 10px;
    margin: 0 16px;
    position: relative;
    flex: 1;
    text-align: center;
}

.zui-import-stat-number {
    font-weight: 700;
    font-size: 30px;
}

.zui-import-stat-heading {
    opacity: 0.7;
}

</style>
