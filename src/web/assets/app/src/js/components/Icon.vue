<script>
import { compile, h } from 'vue';

export default {
    name: 'Icon',

    props: {
        name: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            icon: '',
        };
    },

    created() {
        this.importSvg();
    },

    methods: {
        async importSvg() {
            const svg = await import(`@/js/svg/${this.name}.svg?raw`);

            this.icon = svg.default;
        },
    },

    render() {
        return h(compile(this.icon));
    },
};

</script>
