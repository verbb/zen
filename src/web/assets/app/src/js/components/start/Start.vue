<template>
    <div class="zui-start">
        <import-form />
        <export-form />
    </div>
</template>

<script>
import ImportForm from '@components/start/ImportForm.vue';
import ExportForm from '@components/start/ExportForm.vue';

export default {
    name: 'Start',

    components: {
        ImportForm,
        ExportForm,
    },

    created() {
        this.$store().setSaveButton(this.$route);
        this.$store().setBreadcrumbs(this.$route, ['index']);
    },
};

</script>
